<?php
namespace Sellastica\Entity;

use Sellastica\Entity\Entity\IAggregateMember;
use Sellastica\Entity\Entity\IAggregateRoot;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Relation\ManyToManyRelation;

class UnitOfWork
{
	/** @var array */
	private $entities = [];
	/** @var array */
	private $relations = [];


	/**
	 * @param callable $function
	 * @return IEntity[]
	 */
	public function getEntitiesBy(callable $function): array
	{
		$return = [];
		foreach ($this->entities as $entityType => $entities) {
			foreach ($entities as $key => $entity) {
				if ($this->matchesCallable($entity, $function)) {
					$return[$key] = $entity;
				}
			}
		}

		return $return;
	}

	/**
	 * @param callable $function
	 * @return array
	 */
	public function getUnpersistedEntities(callable $function): array
	{
		$return = [];
		foreach ($this->entities as $class => $entities) {
			/**
			 * @var int $key
			 * @var IEntity $entity
			 */
			foreach ($entities as $key => $entity) {
				if ($entity->getEntityMetadata()->getState()->isNew()
					&& $this->matchesCallable($entity, $function)) {
					$return[$class][] = $entity;
				}
			}
		}

		return $return;
	}

	/**
	 * @param callable $function
	 * @return array
	 */
	public function getPersistedEntities(callable $function): array
	{
		$return = [];
		foreach ($this->entities as $class => $entities) {
			/**
			 * @var int $key
			 * @var IEntity $entity
			 */
			foreach ($entities as $key => $entity) {
				if ($entity->getEntityMetadata()->getState()->isPersisted()
					&& $this->matchesCallable($entity, $function)) {
					$return[$class][] = $entity;
				}
			}
		}

		return $return;
	}

	/**
	 * @param string $className
	 * @return IEntity[]
	 */
	public function getEntitiesByClassName(string $className): array
	{
		return $this->entities[$className] ?? [];
		return $this->getEntitiesBy(function (IEntity $entity) use ($className) {
			return $entity instanceof $className;
		});
	}

	/**
	 * @param IEntity $entity
	 * @param callable $function
	 * @return bool
	 */
	private function matchesCallable(IEntity $entity, callable $function): bool
	{
		return $function($entity);
	}

	/**
	 * @param IEntity $entity
	 */
	public function remove(IEntity $entity)
	{
		$this->attach($entity);
		$entity->setFlag(IEntity::FLAG_REMOVE);
	}

	/**
	 * @param IEntity $entity
	 */
	private function unremove(IEntity $entity)
	{
		$entity->removeFlag(IEntity::FLAG_REMOVE);
	}

	/**
	 * Adds entity to entities (loaded from persistence storage) entites
	 * @param IEntity $entity
	 */
	public function attach(IEntity $entity)
	{
		$this->detach($entity, false);
		$this->entities[get_class($entity)][$this->getKey($entity)] = $entity;
	}

	/**
	 * Detaches entity from the UoW
	 * @param IEntity $entity
	 * @param bool $detachDependencies
	 */
	public function detach(IEntity $entity, bool $detachDependencies = true)
	{
		$this->unremove($entity);
		$this->uncache($entity);
		if (!$detachDependencies) {
			return;
		}

		$this->removeRelationByEntity($entity);
		//remove dependent aggregate members
		if ($entity instanceof IAggregateRoot) {
			$aggregateMembers = $this->getEntitiesBy(function (IEntity $member) use ($entity) {
				return $member instanceof IAggregateMember
					&& $member->getAggregateRootClass() === get_class($entity)
					&& $member->getAggregateId() === $entity->getId();
			});
			foreach ($aggregateMembers as $aggregateMember) {
				$this->unremove($aggregateMember);
				$this->uncache($aggregateMember);
				$this->removeRelationByEntity($aggregateMember);
			}
		}
	}

	/**
	 * Removes from cache
	 * We need to reload entity from the storage to have all its informations, e.g. created and modified date and
	 * all values created as a default value in the storage
	 *
	 * @param IEntity $entity
	 */
	private function uncache(IEntity $entity)
	{
		if (isset($this->entities[get_class($entity)][$this->getKey($entity)])) {
			unset($this->entities[get_class($entity)][$this->getKey($entity)]);
		}
	}

	/**
	 * Loads entity from UoW, searches in all states
	 * @param int $id
	 * @param string $class
	 * @return IEntity|null
	 */
	public function load(int $id, string $class): ?IEntity
	{
		return $this->entities[$class][$id] ?? null;
	}

	/**
	 * @param null|string $entityClass
	 */
	public function clear(string $entityClass = null)
	{
		if ($entityClass) {
			foreach ($this->getEntitiesByClassName($entityClass) as $entity) {
				$this->detach($entity);
			}
		} else {
			foreach ($this->getEntitiesBy(function () {
				return true;
			}) as $entity) {
				$this->unremove($entity);
			}

			$this->relations = [];
		}
	}

	/**
	 * @return array
	 */
	public function getRelations(): array
	{
		return $this->relations;
	}

	/**
	 * @param ManyToManyRelation $relation
	 */
	public function addRelation(ManyToManyRelation $relation)
	{
		$this->relations[get_class($relation->getEntity())][] = $relation;
	}

	/**
	 * @param IEntity $entity
	 */
	public function removeRelationByEntity(IEntity $entity)
	{
		foreach ($this->relations as $className => $relations) {
			foreach ($relations as $key => $relation) {
				/** @var ManyToManyRelation $relation */
				if ($relation->getEntity() instanceof $entity
					|| $relation->getRelatedEntity() instanceof $entity) {
					unset($this->relations[$className][$key]);
				}
			}
		}
	}

	/**
	 * @param IEntity $entity
	 * @return bool
	 */
	public function isCached(IEntity $entity): bool
	{
		return isset($this->entities[get_class($entity)][$this->getKey($entity)]);
	}

	/**
	 * @param IEntity $entity
	 * @return string
	 */
	private function getKey(IEntity $entity): string
	{
		return $entity->getId() ?? spl_object_hash($entity);
	}
}