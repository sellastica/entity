<?php
namespace Sellastica\Entity\Mapping;

use Nette;
use Sellastica\Core\Model\Cache;
use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\EntityCollection;
use Sellastica\Entity\Entity\EntityFactory;
use Sellastica\Entity\Entity\EntityState;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\IBuilder;
use Sellastica\Entity\Relation\ManyToManyRelation;
use Sellastica\Entity\Relation\RelationGetManager;

abstract class Dao implements IDao
{
	/** @var \Sellastica\Entity\Mapping\IMapper */
	protected $mapper;
	/** @var \Sellastica\Core\Model\Cache */
	protected $cache;
	/** @var string */
	protected $entityName;
	/** @var string */
	protected $entityClass;
	/** @var \Sellastica\Entity\Entity\EntityFactory */
	protected $entityFactory;


	/**
	 * @param \Sellastica\Entity\Mapping\IMapper $mapper
	 * @param EntityFactory $entityFactory
	 * @param Nette\Caching\IStorage $storage
	 */
	public function __construct(
		IMapper $mapper,
		EntityFactory $entityFactory,
		Nette\Caching\IStorage $storage
	)
	{
		$this->mapper = $mapper;
		$this->entityFactory = $entityFactory;
		$this->cache = new Cache($storage, Cache::ENTITY_CACHE_NAMESPACE);
	}

	/**
	 * @return Cache
	 */
	public function getEntityCache(): Cache
	{
		return $this->cache;
	}

	/**
	 * @return int
	 */
	public function nextIdentity(): int
	{
		return $this->mapper->nextIdentity();
	}

	/**
	 * @return string
	 */
	protected function getEntityName()
	{
		if (!isset($this->entityName)) {
			$this->entityName = Nette\Utils\Strings::after($this->getEntityClass(), '\\', -1);
		}

		return $this->entityName;
	}

	/**
	 * @return string
	 */
	protected function getEntityClass()
	{
		if (!isset($this->entityClass)) {
			$this->entityClass = Nette\Utils\Strings::before(get_called_class(), 'Dao', -1);
			$this->entityClass = str_replace('Infrastructure\\Mapping', 'Domain\\Model', $this->entityClass);
		}

		return $this->entityClass;
	}

	/**
	 * @param $data
	 * @param mixed $first
	 * @param mixed $second
	 * @return \Sellastica\Entity\Entity\IEntity
	 */
	protected function createEntity($data, $first = null, $second = null)
	{
		$builder = $this->getBuilder($data, $first, $second);
		$entity = $this->entityFactory->build($builder, false);

		$metadata = $entity->getEntityMetadata();
		$metadata->setCreated($data->created);
		$metadata->setModified($data->modified);

		return $entity;
	}

	/**
	 * @param  $data
	 * @param mixed $first
	 * @param mixed $second
	 * @return IBuilder
	 */
	abstract protected function getBuilder($data, $first = null, $second = null): IBuilder;

	/**
	 * @param array $rows
	 * @return EntityCollection
	 */
	protected function createEntityCollection(array $rows)
	{
		$collection = new EntityCollection();
		foreach ($rows as $row) {
			$collection[] = $this->createEntity($row);
		}

		return $collection;
	}

	/**
	 * @param int|null $id
	 * @param mixed $first
	 * @param mixed $second
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	public function find($id, $first = null, $second = null)
	{
		if (empty($id)) {
			return null;
		}

		$cacheKey = $this->getCacheKeyById($id);
		if (!$entity = $this->cache->load($cacheKey)) {
			$row = $this->mapper->find($id);
			// Save loaded model to cache
			if (isset($row)) {
				$entity = $this->createEntity($row, $first, $second);
				$this->saveToChache($cacheKey, $entity, [$this->getTagById($id)]);
			}
		}

		return $entity;
	}

	/**
	 * @param $id
	 * @param array $fields
	 * @return array|null
	 */
	public function findFields($id, array $fields)
	{
		return $this->mapper->findFields($id, $fields);
	}

	/**
	 * @param $id
	 * @param string $field
	 * @return mixed|false
	 */
	public function findField($id, string $field)
	{
		return $this->mapper->findField($id, $field);
	}

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return array
	 */
	public function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array
	{
		return $this->mapper->findFieldBy($field, $filterValues, $configuration);
	}

	/**
	 * Method similar to find(), except that the first parameter is an array with entity IDs
	 *
	 * @param array $idsArray
	 * @param Configuration $configuration
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function findByIds(
		array $idsArray,
		Configuration $configuration = null
	): EntityCollection
	{
		if ($configuration) {
			$idsArray = $this->mapper->findByIds($idsArray, $configuration);
		}

		return $this->getEntitiesFromCacheOrStorage($idsArray);
	}

	/**
	 * @param Configuration $configuration
	 * @param null $first
	 * @param null $second
	 * @return EntityCollection
	 */
	public function findAll(Configuration $configuration = null, $first = null, $second = null): EntityCollection
	{
		$idsArray = $this->mapper->findAllIds($configuration);
		return $this->getEntitiesFromCacheOrStorage($idsArray, $first, $second);
	}

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @param null $first
	 * @param null $second
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function findBy(
		array $filterValues,
		Configuration $configuration = null,
		$first = null,
		$second = null
	): EntityCollection
	{
		$idsArray = $this->mapper->findBy($filterValues, $configuration);
		return $this->getEntitiesFromCacheOrStorage($idsArray, $first, $second);
	}

	/**
	 * @param \Sellastica\Entity\Entity\ConditionCollection $conditions
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	public function findByConditions(
		\Sellastica\Entity\Entity\ConditionCollection $conditions,
		Configuration $configuration = null
	): EntityCollection
	{
		$idsArray = $this->mapper->findByConditions($conditions, $configuration);
		return $this->getEntitiesFromCacheOrStorage($idsArray);
	}

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $modifier
	 * @param Configuration $configuration
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null
	): EntityCollection
	{
		$idsArray = $this->mapper->findIn($column, $values, $modifier, $configuration);
		return $this->getEntitiesFromCacheOrStorage($idsArray);
	}

	/**
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	public function findOneBy(array $filterValues, Configuration $configuration = null): ?IEntity
	{
		$rowId = $this->mapper->findOneBy($filterValues, $configuration);
		return $this->find($rowId);
	}

	/**
	 * @return int
	 */
	public function findCount(): int
	{
		return $this->mapper->findCount();
	}

	/**
	 * @param array $filterValues
	 * @return int
	 */
	public function findCountBy(array $filterValues): int
	{
		return $this->mapper->findCountBy($filterValues);
	}

	/**
	 * @param string|null $key
	 * @param string $value
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return array
	 */
	public function findPairs(
		string $key = null,
		string $value,
		array $filterValues = [],
		Configuration $configuration = null
	): array
	{
		return $this->mapper->findPairs($key, $value, $filterValues, $configuration);
	}

	/**
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	abstract public function getEmptyCollection(): EntityCollection;

	/**
	 * @param array $idsArray
	 * @param mixed $first
	 * @param mixed $second
	 * @return mixed
	 */
	protected function getEntitiesFromCacheOrStorage(array $idsArray, $first = null, $second = null): EntityCollection
	{
		$unloadedIds = [];
		$loadedEntities = [];
		$return = $this->getEmptyCollection();

		if (empty($idsArray)) {
			return $return;
		}

		// Iterate over and try to find in cache
		foreach ($idsArray as $key => $id) {
			if (!is_numeric($id)) {
				continue;
			}

			$entity = $this->cache->load($this->getCacheKeyById($id));
			if (isset($entity)) {
				$return[$key] = $entity;
			} else {
				// Fetch from database - it is a new item
				$unloadedIds[$key] = $id;
			}
		}

		if (!empty($unloadedIds)) {
			foreach ($this->mapper->getEntitiesByIds($unloadedIds) as $key => $row) {
				$entity = $this->createEntity($row, $first, $second);
				$cacheKey = $this->getCacheKeyById($entity->getId());
				$this->saveToChache($cacheKey, $entity, [$this->getTagById($entity->getId())]);
				$loadedEntities[$entity->getId()] = $entity;
			}
		}

		//it has to be sorted in the same order as the original IDs array
		foreach ($idsArray as $key => $id) {
			if (!isset($return[$key]) && isset($loadedEntities[$id])) {
				$return[$key] = $loadedEntities[$id];
				//clear from memory
				unset($loadedEntities[$id]);
			}
		}

		return $return;
	}

	/**
	 * @param string $cacheKey
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @param array $tags
	 */
	private function saveToChache($cacheKey, IEntity $entity, array $tags = [])
	{
		$this->cache->save($cacheKey, $entity, [
			Cache::TAGS => array_unique(
				array_merge([$this->getTagName()], (array)$tags)
			), //1st, 1st/entity, 1st/entity/id
			Cache::EXPIRE => Cache::EXPIRATION,
			Cache::SLIDING => TRUE,
		]);
	}

	/**
	 * Deletes all records
	 */
	public function deleteAll()
	{
		$this->cache->clean([
			Cache::TAGS => [$this->getTagName()]  //1st/entity
		]);
		$this->mapper->deleteAll();
	}

	/**
	 * @param $id
	 */
	public function deleteById($id)
	{
		$this->cache->remove($this->getCacheKeyById($id));
		$this->mapper->deleteById($id);
	}

	/**
	 * @param $id
	 * @return string
	 */
	protected function getCacheKeyById($id)
	{
		return $this->getEntityName() . '/' . $id;
	}

	/**
	 * @return string
	 */
	protected function getTagName(): string
	{
		return $this->getEntityName();
	}

	/**
	 * @param $id
	 * @return string
	 */
	protected function getTagById($id)
	{
		return $this->getCacheKeyById($id);
	}

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return mixed
	 */
	public function getRelationIds(RelationGetManager $relationGetManager): array
	{
		return $this->mapper->getRelationIds($relationGetManager);
	}

	/**
	 * @param RelationGetManager $relationGetManager
	 * @return mixed
	 */
	public function getRelationId(RelationGetManager $relationGetManager)
	{
		return $this->mapper->getRelationId($relationGetManager);
	}

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return mixed
	 */
	public function getRelations(RelationGetManager $relationGetManager): array
	{
		return $this->mapper->getRelations($relationGetManager);
	}

	/**
	 * @param RelationGetManager $relationGetManager
	 * @return mixed
	 */
	public function getRelation(RelationGetManager $relationGetManager)
	{
		return $this->mapper->getRelation($relationGetManager);
	}

	/**
	 * @param \Sellastica\Entity\Relation\ManyToManyRelation $relation
	 */
	public function addRelation(ManyToManyRelation $relation)
	{
		$this->mapper->addRelation($relation);
	}

	/**
	 * @param \Sellastica\Entity\Relation\ManyToManyRelation $relation
	 */
	public function removeRelation(ManyToManyRelation $relation)
	{
		$this->mapper->removeRelation($relation);
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function exists($id): bool
	{
		$entity = $this->find($id);
		return isset($entity);
	}

	/**
	 * @param array $filterValues
	 * @return bool
	 */
	public function existsBy(array $filterValues): bool
	{
		return $this->mapper->existsBy($filterValues);
	}

	/**
	 * @param string $slugWithoutNumbers
	 * @param string $column
	 * @param $id
	 * @param array $groupConditions
	 * @param string $slugNumberDivider
	 * @return array
	 */
	public function findSlugs(
		string $slugWithoutNumbers,
		string $column = 'slug',
		$id = null,
		array $groupConditions = [],
		string $slugNumberDivider = '-'
	): array
	{
		return $this->mapper->findSlugs($slugWithoutNumbers, $column, $id, $groupConditions, $slugNumberDivider);
	}

	/**
	 ****************************************************************
	 ********************** FRONTEND METHODS ************************
	 ****************************************************************
	 */

	/**
	 * @param int|null $id
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	public function findPublishable($id = null)
	{
		if (!isset($id)) {
			return null;
		}

		$id = $this->mapper->findPublishable($id);
		if (isset($id)) {
			return $this->find($id);
		}

		return null;
	}

	/**
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function findAllPublishable(Configuration $configuration = null): EntityCollection
	{
		$idsArray = $this->mapper->findAllPublishableIds($configuration);
		return $this->getEntitiesFromCacheOrStorage($idsArray);
	}

	/**
	 * @param array $filterValues
	 * @return IEntity|null
	 */
	public function findOnePublishableBy(array $filterValues)
	{
		$rowId = $this->mapper->findOnePublishableBy($filterValues);
		return $this->find($rowId);
	}

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return EntityCollection|array
	 */
	public function findPublishableBy(
		array $filterValues,
		Configuration $configuration = null
	)
	{
		$idsArray = $this->mapper->findPublishableBy($filterValues, $configuration);
		return $configuration && $configuration->getRetrieveIds()
			? $idsArray
			: $this->getEntitiesFromCacheOrStorage($idsArray);
	}

	/**
	 * @param array $filterValues
	 * @return int
	 */
	public function findCountOfPublishableBy(array $filterValues): int
	{
		return $this->mapper->findCountOfPublishableBy($filterValues);
	}

	/**
	 * @param int $entityId
	 * @param array $columns
	 */
	public function saveUncachedColumns(int $entityId, array $columns)
	{
		$this->mapper->saveUncachedColumns($entityId, $columns);
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	public function save(IEntity $entity)
	{
		$state = $entity->getEntityMetadata()->getState();
		if ($state->isNew()) {
			$this->mapper->insert($entity);
			$entity->getEntityMetadata()->setState(EntityState::persisted());
		} elseif ($state->isPersisted() && $entity->isChanged()) {
			$this->mapper->update($entity);
		}

		$entity->updateOriginalData();
		$this->cache->remove($this->getCacheKeyById($entity->getId()));
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	public function update(IEntity $entity): void
	{
		$this->mapper->update($entity);
		$entity->updateOriginalData();
		$this->cache->remove($this->getCacheKeyById($entity->getId()));
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity[] $entities
	 */
	public function batchInsert(array $entities): void
	{
		$this->mapper->batchInsert($entities);
		foreach ($entities as $entity) {
			$entity->getEntityMetadata()->setState(EntityState::persisted());
			$entity->updateOriginalData();
			if ($entity->getId()) {
				$this->cache->remove($this->getCacheKeyById($entity->getId()));
			}
		}
	}
}