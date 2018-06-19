<?php
namespace Sellastica\Entity;

use Dibi\Connection;
use Nette\DI\Container;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Sellastica\Entity\Entity\EntityFactory;
use Sellastica\Entity\Entity\IAggregateMember;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Mapping\DibiMapper;
use Sellastica\Entity\Mapping\IRepository;
use Sellastica\Entity\Relation\ManyToManyRelation;

class EntityManager
{
	use SmartObject;

	const STATE_UNLOCKED = 1,
		STATE_LOCKED = 2;

	/** @var array */
	public $onBeforeFlush = [];
	/** @var array */
	public $onFlush = [];
	/** @var array */
	public $onEntityRemoved = [];

	/** @var int */
	private $state = self::STATE_UNLOCKED;
	/** @var Container */
	private $container;
	/** @var UnitOfWork */
	private $unitOfWork;
	/** @var Transaction */
	private $transaction;
	/** @var array */
	private $queue = [];
	/** @var Connection */
	private $connection;
	/** @var Reflections */
	private $reflections;


	/**
	 * @param Container $container
	 * @param Transaction $transaction
	 * @param Connection $connection
	 */
	public function __construct(
		Container $container,
		Transaction $transaction,
		Connection $connection
	)
	{
		$this->container = $container;
		$this->transaction = $transaction;
		$this->connection = $connection;
		$this->unitOfWork = new UnitOfWork();
		$this->reflections = new Reflections();
	}

	private function lock()
	{
		$this->state = self::STATE_LOCKED;
	}

	private function unlock()
	{
		$this->state = self::STATE_UNLOCKED;
	}

	/**
	 * @return bool
	 */
	private function isLocked(): bool
	{
		return $this->state === self::STATE_LOCKED;
	}

	/**
	 * @throws \Exception
	 */
	private function checkLock()
	{
		if ($this->isLocked()) {
			throw new \Exception('Entity manager is locked');
		}
	}

	/**
	 * @return UnitOfWork
	 */
	public function getUnitOfWork(): UnitOfWork
	{
		return $this->unitOfWork;
	}

	/**
	 * @param IEntity|IBuilder $entity
	 * @throws \InvalidArgumentException
	 */
	public function persist($entity)
	{
		$this->checkLock();

		if ($entity instanceof IBuilder) {
			$entity = $entity->build();
		}

		if (!$entity instanceof IEntity) {
			throw new \InvalidArgumentException('Argument must be instance of IEntity or IBuilder');
		}

		if (!$entity->getEntityMetadata()->isInitialized()) {
			$this->getEntityFactory($entity)->initialize($entity);
		}

		//set ID must be after initializing, otherwise incorrect entity state will be detected!
		if (!$entity->getId() && !$entity::isIdGeneratedByStorage()) {
			$this->setEntityId($entity);
		}

		$this->unitOfWork->attach($entity);
	}

	/**
	 * @param IEntity $entity
	 */
	private function setEntityId(IEntity $entity)
	{
		$reflection = new \ReflectionClass($entity);
		$property = $reflection->getProperty('id');
		$property->setAccessible(true);
		$property->setValue($entity, $this->getRepository($entity)->nextIdentity());
	}

	/**
	 * @param IEntity $entity
	 * @param bool $immediately
	 */
	public function remove(IEntity $entity, bool $immediately = false)
	{
		$this->checkLock();
		if ($immediately) {
			$this->delete($entity);
		} else {
			$this->unitOfWork->remove($entity);
		}
	}

	/**
	 * @param string $entityClass
	 */
	public function removeAll(string $entityClass)
	{
		foreach ($this->unitOfWork->getEntitiesByClassName($entityClass) as $entity) {
			$this->unitOfWork->detach($entity);
		}

		$this->getDao($entityClass)->deleteAll();
	}

	/**
	 * @param IEntity $entity
	 */
	public function attach(IEntity $entity)
	{
		$this->unitOfWork->attach($entity);
	}

	/**
	 * @param IEntity $entity
	 */
	public function detach(IEntity $entity)
	{
		$this->unitOfWork->detach($entity);
	}

	/**
	 * @param \Sellastica\Entity\Relation\ManyToManyRelation $relation
	 */
	public function addRelation(ManyToManyRelation $relation)
	{
		$this->checkLock();
		$this->unitOfWork->addRelation($relation);
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	private function afterSave(IEntity $entity)
	{
		//call events
		foreach ($entity->onSave as $function) {
			call_user_func($function, $entity);
		}

		$this->addToQueue($entity);
	}

	/**
	 * Flushes all changes and saves queue into the persistence storage
	 */
	public function flush()
	{
		$this->onBeforeFlush();
		$this->onBeforeFlush = [];
		$this->lock();
		$this->transaction->begin();

		try {
			$this->updateModifiedTimestamps();

			//remove entities
			$entitiesToRemove = $this->unitOfWork->getEntitiesBy(function (IEntity $entity) {
				return $entity->shouldRemove();
			});
			foreach ($entitiesToRemove as $entity) {
				$this->delete($entity);
			}

			//aggreate roots and standard entities
			$this->setForeignKeyCheck(false);

			//update
			foreach ($this->unitOfWork->getPersistedEntities(function (IEntity $entity) {
				return $entity->shouldPersist()
					&& $entity->isChanged()
					&& !$this->isInQueue($entity)
					&& !$entity->shouldRemove();
			}) as $class => $entities) {
				foreach ($entities as $entity) {
					$this->getDao($class)->update($entity);
					$this->afterSave($entity);
				}
			}

			//insert
			foreach ($this->unitOfWork->getUnpersistedEntities(function (IEntity $entity) {
				return $entity->shouldPersist()
					&& $entity->isChanged()
					&& !$this->isInQueue($entity)
					&& !$entity->shouldRemove();
			}) as $class => $entities) {
				$this->getDao($class)->batchInsert($entities);
				foreach ($entities as $entity) {
					$this->afterSave($entity);
				}
			}

			$this->setForeignKeyCheck(true);

			//relations
			foreach ($this->unitOfWork->getRelations() as $relations) {
				foreach ($relations as $relation) {
					/** @var ManyToManyRelation $relation */
					if ($relation->shouldPersist()) {
						$this->getDao($relation->getEntity())->addRelation($relation);
					} elseif ($relation->shouldRemove()) {
						$this->getDao($relation->getEntity())->removeRelation($relation);
					}
				}
			}
		} catch (\Throwable $e) {
			$this->transaction->rollback();
			$this->rollbackQueue();
			$this->setForeignKeyCheck(true);
			throw $e;
		}

		$this->transaction->commit();
		$this->unlock();
		$this->queue = [];
		$this->unitOfWork->clearRelations();

		$this->onFlush();
		$this->onFlush = [];
	}

	private function updateModifiedTimestamps(): void
	{
		$dateTime = new \DateTime();

		//aggreate members - modified timestamp change only
		$aggregateMembers = function (IEntity $entity) {
			return $entity instanceof IAggregateMember
				&& ($entity->shouldPersist() || $entity->shouldRemove())
				&& $entity->isChanged();
		};
		foreach ($this->unitOfWork->getEntitiesBy($aggregateMembers) as $aggregateMember) {
			/** @var IAggregateMember $aggregateMember */
			if ($aggregateMember->getAggregateRoot()) {
				$aggregateMember->getAggregateRoot()->getEntityMetadata()->setModified($dateTime);
			}
		}

		//relations - modified timestamp change only
		foreach ($this->unitOfWork->getRelations() as $relations) {
			foreach ($relations as $relation) {
				/** @var ManyToManyRelation $relation */
				if ($relation->shouldPersist() || $relation->shouldRemove()) {
					$relation->getEntity()->getEntityMetadata()->setModified($dateTime);
					$relation->getRelatedEntity()->getEntityMetadata()->setModified($dateTime);
				}
			}
		}
	}

	/**
	 * @param bool $check
	 */
	private function setForeignKeyCheck(bool $check)
	{
		$this->connection->query('SET FOREIGN_KEY_CHECKS = %i', $check);
	}

	/**
	 * @param IEntity $entity
	 */
	private function delete(IEntity $entity)
	{
		$this->getDao($entity)->deleteById($entity->getId());
		$this->unitOfWork->removeRelationByEntity($entity);

		//call events
		foreach ($entity->onRemove as $function) {
			call_user_func($function, $entity);
		}

		$this->unitOfWork->detach($entity);
		$this->addToQueue($entity);
		$this->onEntityRemoved($entity);
	}

	/**
	 * @param IEntity $entity
	 * @return bool
	 */
	private function isInQueue(IEntity $entity): bool
	{
		return isset($this->queue[get_class($entity)][$entity->getId()]);
	}

	/**
	 * @param IEntity $entity
	 */
	private function addToQueue(IEntity $entity)
	{
		$this->queue[get_class($entity)][$entity->getId()] = $entity;
	}

	private function rollbackQueue(): void
	{
		//@TODO: detached entities lost their flags!
		foreach ($this->queue as $entities) {
			foreach ($entities as $entity) {
				$this->unitOfWork->attach($entity);
			}
		}
	}

	/**
	 * @param IEntity|string $entity
	 * @return \Sellastica\Entity\Mapping\IDao|object
	 */
	public function getDao($entity)
	{
		if ($entity instanceof IEntity) {
			$entity = $entity::getShortName();
		}

		$this->assertEntityClass($entity);
		if (strpos($entity, '\\') !== false) {
			$entity = Strings::after($entity, '\\', -1);
		}

		return $this->container->getService(Strings::firstLower($entity) . 'Dao');
	}

	/**
	 * @param IEntity|string $entity
	 * @return \Sellastica\Entity\Mapping\IRepository|object
	 */
	public function getRepository($entity): IRepository
	{
		if ($entity instanceof IEntity) {
			$entity = $entity::getShortName();
		}

		$this->assertEntityClass($entity);
		if (strpos($entity, '\\') !== false) {
			$entity = Strings::after($entity, '\\', -1);
		}

		return $this->container->getService(Strings::firstLower($entity) . 'Repository');
	}

	/**
	 * @param IEntity|string $entity
	 * @return \Sellastica\Entity\Entity\EntityFactory|object
	 */
	public function getEntityFactory($entity): EntityFactory
	{
		if ($entity instanceof IEntity) {
			$entity = $entity::getShortName();
		}

		$this->assertEntityClass($entity);
		return $this->container->getService(Strings::firstLower($entity) . 'Factory');
	}

	/**
	 * @param $entity
	 * @throws \InvalidArgumentException
	 */
	private function assertEntityClass($entity)
	{
		if (!is_string($entity)) {
			throw new \InvalidArgumentException('Parameter $entity must be either instance of IEntity or a string');
		}
	}

	/**
	 * @param int $multipleInsertsCount
	 */
	public function optimizeImports(int $multipleInsertsCount = 10)
	{
		DibiMapper::$multipleInsertsCount = $multipleInsertsCount;
	}

	public function clear(): void
	{
		$this->unitOfWork->clear();
	}

	/**
	 * @param IEntity $entity
	 * @return \Nette\Reflection\ClassType
	 */
	public function getReflection(IEntity $entity): \Nette\Reflection\ClassType
	{
		return $this->reflections->getReflection($entity);
	}
}