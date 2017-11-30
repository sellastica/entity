<?php
namespace Sellastica\Entity\Mapping;

use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\EntityCollection;
use Sellastica\Entity\Entity\EntityFactory;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\EntityManager;
use Sellastica\Entity\Relation\RelationGetManager;

abstract class Repository implements IRepository
{
	/** @var IDao */
	protected $dao;
	/** @var \Sellastica\Entity\Entity\EntityFactory */
	protected $entityFactory;
	/** @var EntityManager */
	private $em;


	/**
	 * @param IDao $dao
	 * @param \Sellastica\Entity\Entity\EntityFactory $entityFactory
	 * @param EntityManager $em
	 */
	public function __construct(
		IDao $dao,
		EntityFactory $entityFactory,
		EntityManager $em
	)
	{
		$this->dao = $dao;
		$this->entityFactory = $entityFactory;
		$this->em = $em;
	}

	/**
	 * @return int
	 */
	public function nextIdentity(): int
	{
		return $this->dao->nextIdentity();
	}

	/**
	 * @param int|string|null $id If string, then only in format application:externalId
	 * @param null $first
	 * @param null $second
	 * @return IEntity|null
	 */
	public function find($id = null, $first = null, $second = null): ?IEntity
	{
		if (empty($id)) {
			return null;
		}

		if (!$entity = $this->em->getUnitOfWork()->load($id, $this->entityFactory->getEntityClass())) {
			$entity = $this->dao->find($id, $first, $second);
			$entity = $this->initialize($entity, $first, $second);
		}

		return $entity;
	}

	/**
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 */
	public function findFields(int $id, array $fields)
	{
		return $this->dao->findFields($id, $fields);
	}

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @return array
	 */
	public function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array
	{
		return $this->dao->findFieldBy($field, $filterValues, $configuration);
	}

	/**
	 * @param int $id
	 * @param string $field
	 * @return mixed|false
	 */
	public function findField(int $id, string $field)
	{
		return $this->dao->findField($id, $field);
	}

	/**
	 * @param array $idsArray
	 * @param Configuration|null $configuration
	 * @return EntityCollection
	 */
	public function findByIds(
		array $idsArray,
		Configuration $configuration = null
	): EntityCollection
	{
		$entities = $this->dao->findByIds($idsArray, $configuration);
		return $this->initialize($entities);
	}

	/**
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	public function findAll(Configuration $configuration = null): EntityCollection
	{
		$entities = $this->dao->findAll($configuration);
		return $this->initialize($entities);
	}

	/**
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @param null $first
	 * @param null $second
	 * @return EntityCollection
	 */
	public function findBy(
		array $filterValues,
		Configuration $configuration = null,
		$first = null,
		$second = null
	): EntityCollection
	{
		$entities = $this->dao->findBy($filterValues, $configuration, $first, $second);
		return $this->initialize($entities, $first, $second);
	}

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $modifier
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null
	): EntityCollection
	{
		$entities = $this->dao->findIn($column, $values, $modifier, $configuration);
		return $this->initialize($entities);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function exists(int $id): bool
	{
		return $this->dao->exists($id);
	}

	/**
	 * @param array $filterValues
	 * @return bool
	 */
	public function existsBy(array $filterValues): bool
	{
		return $this->dao->existsBy($filterValues);
	}

	/**
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return IEntity|null
	 */
	public function findOneBy(array $filterValues, Configuration $configuration = null): ?IEntity
	{
		$entity = $this->dao->findOneBy($filterValues, $configuration);
		return $this->initialize($entity);
	}

	/**
	 * @return int
	 */
	public function findCount(): int
	{
		return $this->dao->findCount();
	}

	/**
	 * @param array $filterValues
	 * @return int
	 */
	public function findCountBy(array $filterValues): int
	{
		return $this->dao->findCountBy($filterValues);
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return array
	 */
	public function findPairs(
		$key,
		$value,
		array $filterValues = [],
		Configuration $configuration = null
	): array
	{
		return $this->dao->findPairs($key, $value, $filterValues, $configuration);
	}

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array
	 */
	public function getRelationIds(RelationGetManager $relationGetManager): array
	{
		return $this->dao->getRelationIds($relationGetManager);
	}

	/**
	 * @param RelationGetManager $relationGetManager
	 * @return mixed
	 */
	public function getRelationId(RelationGetManager $relationGetManager)
	{
		return $this->dao->getRelationId($relationGetManager);
	}

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array
	 */
	public function getRelations(RelationGetManager $relationGetManager): array
	{
		return $this->dao->getRelations($relationGetManager);
	}

	/**
	 * @param RelationGetManager $relationGetManager
	 * @return array|null
	 */
	public function getRelation(RelationGetManager $relationGetManager)
	{
		return $this->dao->getRelation($relationGetManager);
	}

	/**
	 * @param mixed $entities
	 * @param mixed $first
	 * @param mixed $second
	 * @return IEntity|mixed
	 */
	final protected function initialize($entities, $first = null, $second = null)
	{
		if ($entities instanceof IEntity) {
			//avoid using call_user_func_array because of performance
			return $this->initializeEntity($entities, $first, $second);
		} elseif (is_array($entities) || $entities instanceof \Traversable) {
			foreach ($entities as $key => $entity) {
				if ($entity instanceof IEntity) {
					$entities[$key] = $this->initializeEntity($entity, $first, $second);
				}
			}
		}

		return $entities;
	}

	/**
	 * @param IEntity $entity
	 * @param mixed $first
	 * @param mixed $second
	 * @return IEntity
	 */
	private function initializeEntity(IEntity $entity, $first = null, $second = null): IEntity
	{
		if ($cached = $this->em->getUnitOfWork()->load($entity->getId(), $this->entityFactory->getEntityClass())) {
			return $cached;
		}

		//initializing has to be after attaching, otherwise it may cause looping, if initialize method
		//searches aggregate root for an aggregate member (root is still not in the UoW)
		$this->entityFactory->initialize($entity, $first, $second);
		$this->em->attach($entity);
		return $entity;
	}

	/**
	 ****************************************************************
	 ********************** FRONTEND METHODS ************************
	 ****************************************************************
	 */

	/**
	 * @param int|null $id
	 * @return IEntity|null
	 */
	public function findPublishable(int $id = null)
	{
		if (!isset($id)) {
			return null;
		}

		if (!$entity = $this->em->getUnitOfWork()->load($id, $this->entityFactory->getEntityClass())) {
			$entity = $this->dao->findPublishable($id);
			$entity = $this->initialize($entity);
		}

		return $entity;
	}

	/**
	 * @param Configuration $configuration
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function findAllPublishable(Configuration $configuration = null): EntityCollection
	{
		$entities = $this->dao->findAllPublishable($configuration);
		return $this->initialize($entities);
	}

	/**
	 * @param array $filterValues
	 * @return IEntity|null
	 */
	public function findOnePublishableBy(array $filterValues)
	{
		$entity = $this->dao->findOnePublishableBy($filterValues);
		return $this->initialize($entity);
	}

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return \Sellastica\Entity\Entity\EntityCollection|array
	 */
	public function findPublishableBy(
		array $filterValues,
		Configuration $configuration = null
	)
	{
		$entities = $this->dao->findPublishableBy($filterValues, $configuration);
		return $this->initialize($entities);
	}

	/**
	 * @param array $filterValues
	 * @return int
	 */
	public function findCountOfPublishableBy(array $filterValues): int
	{
		return $this->dao->findCountOfPublishableBy($filterValues);
	}

	/**
	 * @param int $entityId
	 * @param array $columns
	 */
	public function saveUncachedColumns(int $entityId, array $columns)
	{
		$this->dao->saveUncachedColumns($entityId, $columns);
	}

	/**
	 * @param string $slugWithoutNumbers
	 * @param string $column
	 * @param int $id
	 * @param array $groupConditions
	 * @param string $slugNumberDivider
	 * @return array
	 */
	public function findSlugs(
		string $slugWithoutNumbers,
		string $column = 'slug',
		int $id = null,
		array $groupConditions = [],
		string $slugNumberDivider = '-'
	): array
	{
		return $this->dao->findSlugs($slugWithoutNumbers, $column, $id, $groupConditions, $slugNumberDivider);
	}
}