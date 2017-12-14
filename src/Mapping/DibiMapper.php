<?php
namespace Sellastica\Entity\Mapping;

use Dibi;
use Dibi\Fluent;
use Nette;
use Nette\Utils\Paginator;
use Sellastica\Core\Model\Cache;
use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Exception\StorageDuplicateEntryException;
use Sellastica\Entity\Exception\StorageException;
use Sellastica\Entity\Relation\ManyToManyRelation;
use Sellastica\Entity\Relation\RelationGetManager;
use Sellastica\Entity\Sorter;
use Sellastica\Utils\Strings;

abstract class DibiMapper implements IMapper
{
	const CACHE_ACTIVE = false,
		MIN_AUTOINCREMENT = 1000001;

	/** @var array */
	private static $identities = [];
	/** @var int */
	public static $multipleInsertsCount = 0;
	/** @var Dibi\Connection */
	protected $database;
	/** @var Cache */
	protected $cache;
	/** @var \Sellastica\Core\Model\Environment */
	protected $environment;
	/** @var string In case of mysql storage it is a table name */
	private $tableName;


	/**
	 * @param Nette\DI\Container $container
	 * @param Nette\Caching\IStorage $storage
	 * @param \Sellastica\Core\Model\Environment $environment
	 * @throws Nette\DI\MissingServiceException
	 */
	public function __construct(
		Nette\DI\Container $container,
		Nette\Caching\IStorage $storage,
		\Sellastica\Core\Model\Environment $environment
	)
	{
		$this->cache = new Cache($storage, Cache::QUERY_CACHE_NAMESPACE);
		$this->database = $container->getService('dibi');
		$this->environment = $environment;
	}

	/**
	 * @return bool
	 */
	protected function isInCrmDatabase(): bool
	{
		return false;
	}

	/**
	 * @param bool $databaseName
	 * @return string
	 */
	protected function getTableName($databaseName = false): string
	{
		if (!isset($this->tableName)) {
			$name = lcfirst(preg_replace('~.+\\\(.+)~', '$1', get_called_class()));
			$name = Nette\Utils\Strings::before($name, 'DibiMapper', -1);
			$this->tableName = strtolower(preg_replace('~([A-Z])~', '_$1', $name));
		}

		if (true === $databaseName
			&& $this->isInCrmDatabase()) {
			return $this->environment->getCrmDatabaseName() . '.' . $this->tableName;
		} else {
			return $this->tableName;
		}
	}

	/**
	 * @return string
	 */
	protected function getEntityName(): string
	{
		return ucfirst(Strings::toCamelCase($this->getTableName()));
	}

	/**
	 * @param string $className
	 * @return string
	 */
	protected function getTableNameByClassName(string $className)
	{
		$shortName = strpos($className, '\\') !== false
			? Strings::after($className, '\\', -1)
			: $className;
		return Strings::fromCamelCase($shortName);
	}

	/**
	 * @param Fluent $resource
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @return Fluent
	 */
	protected function applyConfiguration(Fluent $resource, Configuration $configuration = null): Fluent
	{
		//paginator, sorter
		if (isset($configuration)) {
			$this->setPaginator($resource, $configuration->getPaginator());
			$this->setSorter($resource, $configuration->getSorter());
			if ($configuration->getLastModified()) {
				$resource->where('%n.modified > %t', $this->getTableName(), $configuration->getLastModified());
			}
		}

		return $resource;
	}

	/**
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return Fluent
	 */
	protected function getResource(Configuration $configuration = null)
	{
		return $this->database
			->select('%n.*', $this->getTableName())
			->from($this->getTableName(true));
	}

	/**
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return Fluent
	 */
	public function getResourceWithIds(Configuration $configuration = null)
	{
		return $this->getResource($configuration)
			->select(false)
			->select('%n.id', $this->getTableName());
	}

	/**
	 * @param array $arrayWithIds
	 * @param string $column
	 * @return array
	 */
	protected function getArray($arrayWithIds, $column = 'id')
	{
		$simpleArray = [];
		if (!empty($arrayWithIds)) {
			foreach ($arrayWithIds as $row) {
				$simpleArray[] = $row->$column;
			}
		}

		return $simpleArray;
	}

	/**
	 * @return int
	 */
	public function nextIdentity(): int
	{
		$id = $this->insertNextIdentity();
		if ($id < self::MIN_AUTOINCREMENT) {
			$id = $this->insertNextIdentity(self::MIN_AUTOINCREMENT);
		}

		return $id;
	}

	/**
	 * @param int|null $id
	 * @return int
	 * @throws StorageException
	 */
	private function insertNextIdentity(int $id = null)
	{
		if (!self::$multipleInsertsCount) {
			//default behaviour
			return $this->database->insert('_id_generator', ['id' => $id])
				->execute(\dibi::IDENTIFIER);
		} else {
			//imports optimized
			if (!sizeof(self::$identities)) {
				$uniqId = uniqid();
				$array = array_fill(0, self::$multipleInsertsCount, [
					'id' => null,
					'uid' => $uniqId,
				]);
				$this->database->query('INSERT INTO [_id_generator] %ex', $array);

				//we need to use this trick, because we cannot work with last insert ID
				//because if mysql innodb_autoinc_lock_mode = 2, it is not sure that IDs will be consecutive
				if (!self::$identities = $this->database->select('id')
					->from('_id_generator')
					->where('uid = %s', $uniqId)
					->fetchPairs()) {
					throw new StorageException('Autoincrement ID could not be generated');
				}
			}

			return array_shift(self::$identities);
		}
	}

	/**
	 * @param int $id
	 * @return Dibi\Row|null
	 */
	public function find(int $id)
	{
		if ($id) {
			$row = $this->getResource()
				->where('%n.id = %i', $this->getTableName(), $id)
				->fetch();
			if (false !== $row) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * @param int $id
	 * @param string $field
	 * @return mixed|false
	 */
	public function findField(int $id, string $field)
	{
		return $this->getResource()
			->select(false)
			->select($field)
			->where('%n.id = %i', $this->getTableName(), $id)
			->fetchSingle();
	}

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @return array
	 */
	public function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array
	{
		$result = $this->getResource()
			->select(false)
			->select($field)
			->where($filterValues);

		if (isset($configuration)) {
			$this->applyConfiguration($result, $configuration);
		}

		return $this->getArray($result->fetchAll(), $field);
	}

	/**
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 */
	public function findFields(int $id, array $fields)
	{
		$result = $this->getResource()
			->select(false)
			->select($fields)
			->where('%n.id = %i', $this->getTableName(), $id)
			->fetch();

		return $result ? (array)$result : null;
	}

	/**
	 * @param array $idsArray
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @param Fluent $resource
	 * @return array
	 */
	public function findByIds(
		array $idsArray,
		Configuration $configuration = null,
		Fluent $resource = null
	): array
	{
		$resource = $resource ?: $this->getResourceWithIds($configuration);
		$resource->where('%n.id IN (%iN)', $this->getTableName(), $idsArray);
		$this->applyConfiguration($resource, $configuration);

		return $this->fetchArray($resource);
	}

	/**
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @param Fluent $resource
	 * @return array
	 */
	public function findAllIds(
		Configuration $configuration = null,
		Fluent $resource = null
	): array
	{
		$resource = $resource ?: $this->getResourceWithIds();
		$this->applyConfiguration($resource, $configuration);

		return $this->fetchArray($resource);
	}

	/**
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @param Fluent $resource
	 * @return array
	 */
	public function findBy(
		array $filterValues,
		Configuration $configuration = null,
		Fluent $resource = null
	): array
	{
		$resource = $resource ?: $this->getResourceWithIds($configuration);
		$resource->where($filterValues);
		$this->applyConfiguration($resource, $configuration);

		return $this->fetchArray($resource);
	}

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $modifier
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @param Fluent $resource
	 * @return array
	 */
	public function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null,
		Fluent $resource = null
	): array
	{
		$resource = $resource ?: $this->getResourceWithIds($configuration);
		$resource->where('%n IN (%' . $modifier . 'N)', $column, $values);
		$this->applyConfiguration($resource, $configuration);

		return $this->fetchArray($resource);
	}

	/**
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @param Fluent $resource
	 * @return false|int Result ID
	 */
	public function findOneBy(
		array $filterValues,
		Configuration $configuration = null,
		Fluent $resource = null
	)
	{
		$resource = $resource ?: $this->getResourceWithIds();
		$resource->where($filterValues);
		$this->applyConfiguration($resource, $configuration);
		return $this->fetchSingle($resource);
	}

	/**
	 * @param Fluent $resource
	 * @return int
	 */
	public function findCount(Fluent $resource = null): int
	{
		$resource = $resource ?: $this->getResource();
		$resource->select(false)
			->select('COUNT(*)');

		return (int)$this->fetchSingle($resource);
	}

	/**
	 * @param array $filterValues
	 * @param Fluent $resource
	 * @return int
	 */
	public function findCountBy(array $filterValues, Fluent $resource = null): int
	{
		$resource = $resource ?: $this->getResource();
		$resource->select(false)
			->select('COUNT(*)')
			->where($filterValues);

		return (int)$this->fetchSingle($resource);
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return array
	 */
	public function findPairs($key, $value, array $filterValues = [], Configuration $configuration = null): array
	{
		$resource = $this->getResource($configuration)
			->select(false)
			->select('%n.%n, %n.%n', $this->getTableName(), $key, $this->getTableName(), $value);

		if (sizeof($filterValues)) {
			$resource->where($filterValues);
		}

		$this->applyConfiguration($resource, $configuration);

		return $this->fetchPairs($resource, $key, $value);
	}

	/**
	 * Sets count of items in the paginator
	 * It does own count - dibi countable is slow because do a tmp table wrapper
	 *
	 * @param Paginator $pagination
	 * @param Fluent $resource
	 */
	protected function setPaginator(
		Fluent $resource,
		Paginator $pagination = null
	)
	{
		if (is_null($pagination)) {
			return;
		}

		$paginatorResource = clone $resource;
		$count = $this->database->select('COUNT(*)')
			->from($paginatorResource)->as('tmp')
			->fetchSingle();


		//apply paginator into the resource
		$resource->limit($pagination->getLength())
			->offset($pagination->getOffset());

		$pagination->setItemCount($count);
	}

	/**
	 * @param Fluent $resource
	 * @param \Sellastica\Entity\Sorter $sorter
	 * @return Fluent
	 */
	protected function setSorter(
		Fluent $resource,
		Sorter $sorter = null
	)
	{
		if (!is_null($sorter)) {
			foreach ($sorter->getRules() as $rule) {
				$resource->orderBy($rule->getColumn(), $rule->getDirection());
			}
		}

		return $resource;
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @throws Dibi\Exception
	 */
	public function update(IEntity $entity)
	{
		$diff = $entity->getChangedData();
		if (!empty($diff)) {
			try {
				$this->database
					->update($this->getTableName(true), $diff)
					->where('%n.id = %i', $this->getTableName(), $entity->getId())
					->execute();
			} catch (Dibi\Exception $e) {
				$this->throwException($e);
			}

			//first level (entity) cache is in save methods used
			//we have clean second level cache vice versa because relations could change
			$this->cleanCache(); //2nd/entity
		}
	}

	/**
	 * @param IEntity $entity
	 * @throws Dibi\Exception
	 * @throws StorageDuplicateEntryException
	 * @throws StorageException
	 */
	public function insert(IEntity $entity): void
	{
		try {
			$this->database
				->insert($this->getTableName(true), $entity->toArray())
				->execute();

			if (!$entity->getId()) {
				$reflection = new \ReflectionClass($entity);
				$property = $reflection->getProperty('id');
				$property->setAccessible(true);
				$property->setValue($entity, $this->database->getInsertId());
			}
		} catch (Dibi\Exception $e) {
			$this->throwException($e);
		}
	}

	/**
	 * @param IEntity[] $entities
	 */
	public function batchInsert(array $entities): void
	{
		if (sizeof($entities) === 1) {
			$this->insert($entities[0]);
		} else {
			try {
				$values = [];
				foreach ($entities as $entity) {
					$values[] = $entity->toArray();
				}

				if (!empty($values)) {
					$this->database->query(
						'INSERT INTO [' . $this->getTableName(true) . '] %ex', $values
					);
				}
			} catch (Dibi\Exception $e) {
				$this->throwException($e);
			}
		}
	}

	/**
	 * @param int $entityId
	 * @param array $columns
	 */
	public function saveUncachedColumns(int $entityId, array $columns)
	{
		$this->database->update($this->getTableName(true), $columns)
			->where('id = %i', $entityId)
			->execute();
	}

	/**
	 * @param array $ids
	 * @return array The returned array is associative due to sorting
	 *      in the repository/getEntitiesFromCacheOrStorage method
	 */
	public function getEntitiesByIds(array $ids): array
	{
		$resource = $this->getResource()
			->where('%n.id IN (%iN)', $this->getTableName(), $ids);
		return $this->fetchAll($resource);
	}

	/**
	 * Truncates the table
	 */
	public function deleteAll()
	{
		$this->database->query('DELETE FROM %n', $this->getTableName(true));
		$this->cleanAllCache(); //2nd
	}

	/**
	 * @param int $id
	 */
	public function deleteById(int $id)
	{
		$this->database
			->delete($this->getTableName(true))
			->where('%n.id = %i', $this->getTableName(), $id)
			->execute();
		$this->cleanAllCache(); //2nd - because of foreign key storage dependencies
	}

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return Fluent
	 */
	private function getRelationResource(RelationGetManager $relationGetManager)
	{
		$resource = $this->database->select('%n.*', $relationGetManager->getStorage())
			->from($relationGetManager->getStorage());

		foreach ($relationGetManager->getEntities() as $entity) {
			$resource->where("{$entity[0]} = %s", $entity[1]);
		}

		return $resource;
	}

	/**
	 * Finds one column from relation table and returns as simple array
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array
	 */
	public function getRelationIds(RelationGetManager $relationGetManager): array
	{
		$resource = $this->getRelationResource($relationGetManager)
			->select(false)
			->select($relationGetManager->getResultEntityId());

		$resultArray = [];
		foreach ($this->fetchAll($resource) as $row) {
			if ($relationGetManager->getRelationKey()) {
				$resultArray[$row->{$relationGetManager->getRelationKey()}] = $row->{$relationGetManager->getResultEntityId()};
			} else {
				$resultArray[] = $row->{$relationGetManager->getResultEntityId()};
			}
		}

		return $resultArray;
	}

	/**
	 * Finds one single result from relation table and returns as string or integer
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function getRelationId(RelationGetManager $relationGetManager)
	{
		if (!$relationGetManager->getResultEntityId()) {
			throw new \InvalidArgumentException('Relation entity identifier not defined');
		}

		$resource = $this->getRelationResource($relationGetManager)
			->select(false)
			->select($relationGetManager->getResultEntityId());

		return $this->fetchSingle($resource);
	}

	/**
	 * Finds rows (all columns) from relation table and returns as simple array or indexed array
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array
	 */
	public function getRelations(RelationGetManager $relationGetManager): array
	{
		$resource = $this->getRelationResource($relationGetManager);

		$resultArray = [];
		foreach ($this->fetchAll($resource) as $row) {
			if ($relationGetManager->getRelationKey()) {
				$resultArray[$row->{$relationGetManager->getRelationKey()}] = $row;
			} else {
				$resultArray[] = $row;
			}
		}

		return $resultArray;
	}

	/**
	 * Finds one row (all columns) from relation table and returns as Dibi row object
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array|null
	 */
	public function getRelation(RelationGetManager $relationGetManager)
	{
		$relation = $this->fetch($this->getRelationResource($relationGetManager));
		return $relation ? $relation : null;
	}

	/**
	 * @param ManyToManyRelation $relation
	 */
	public function addRelation(ManyToManyRelation $relation)
	{
		$tableName = $relation->getTableName();
		$resource = $this->database
			->insert($tableName, $relation->toArray());

		try {
			$resource->execute();
		} catch (Dibi\Exception $e) {
			$this->throwException($e);
		}

		$this->cleanCache($tableName);
	}

	/**
	 * @param ManyToManyRelation $relation
	 */
	public function removeRelation(ManyToManyRelation $relation)
	{
		$tableName = $relation->getTableName();
		$resource = $this->database->delete($tableName)
			->where("(%and)", $relation->toArray());

		try {
			$resource->execute();
		} catch (Dibi\Exception $e) {
			$this->throwException($e);
		}
	}

	/**
	 * @param array $filterValues
	 * @return bool
	 */
	public function existsBy(array $filterValues): bool
	{
		$resource = $this->getResource()
			->select(false)
			->select(1)
			->where($filterValues);

		return (bool)$this->fetchSingle($resource);
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
		$filterValues = array_merge(
			$groupConditions, [
			sprintf('[%s] REGEXP "^%s(\%s[0-9]+)?$"', $column, $slugWithoutNumbers, $slugNumberDivider),
		]);
		$resource = $this->getResource()
			->select(false)
			->select($column)
			->where($filterValues);

		if (null !== $id) {
			$resource->where('%n.id != %i', $this->getTableName(), $id);
		}

		return $this->getArray($resource->fetchAll(), $column);
	}

	/**
	 * @param Dibi\Exception $e
	 * @throws StorageDuplicateEntryException
	 * @throws StorageException
	 */
	private function throwException(Dibi\Exception $e)
	{
		throw new StorageException($e->getMessage(), $e->getCode(), $e->getSql());
	}

	/**
	 ****************************************************************
	 ********************** FRONTEND METHODS ************************
	 ****************************************************************
	 */

	/**
	 * This method is often overridden in entity mapper
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @return Fluent
	 */
	protected function getPublishableResource(Configuration $configuration = null)
	{
		return $this->getResource($configuration);
	}

	/**
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return Fluent
	 */
	protected function getPublishableResourceWithIds(Configuration $configuration = null)
	{
		return $this->getPublishableResource($configuration)
			->select(false)
			->select('%n.id', $this->getTableName());
	}

	/**
	 * @param int $id
	 * @return int|null
	 */
	public function findPublishable(int $id)
	{
		if ($id) {
			$resource = $this->getPublishableResourceWithIds()
				->where('%n.id = %i', $this->getTableName(), $id);
			return $this->fetchSingle($resource);
		}

		return null;
	}

	/**
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @return array
	 */
	public function findAllPublishableIds(Configuration $configuration = null): array
	{
		return $this->findAllIds($configuration, $this->getPublishableResourceWithIds());
	}

	/**
	 * @param array $filterValues
	 * @return int|false
	 */
	public function findOnePublishableBy(array $filterValues)
	{
		return $this->findOneBy($filterValues, null, $this->getPublishableResourceWithIds());
	}

	/**
	 * @param array $filterValues
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @return array
	 */
	public function findPublishableBy(
		array $filterValues,
		Configuration $configuration = null
	): array
	{
		return $this->findBy($filterValues, $configuration, $this->getPublishableResourceWithIds());
	}

	/**
	 * @param array $filterValues
	 * @return int
	 */
	public function findCountOfPublishableBy(array $filterValues): int
	{
		return $this->findCountBy($filterValues, $this->getPublishableResourceWithIds());
	}

	/**
	 ****************************************************************
	 ********************** BACKEND METHODS *************************
	 ****************************************************************
	 */

	/**
	 * @param \Sellastica\Entity\Configuration|null $configuration
	 * @return Fluent
	 */
	protected function getAdminResource(Configuration $configuration = null): Fluent
	{
		return $this->getResource($configuration);
	}

	/**
	 * @param Configuration|null $configuration
	 * @return Fluent
	 */
	protected function getAdminResourceWithIds(Configuration $configuration = null): Fluent
	{
		return $this->getAdminResource($configuration)
			->select(false)
			->select('%n.id', $this->getTableName());
	}

	/**
	 ****************************************************************
	 ********************** SECOND LEVEL CACHE **********************
	 ****************************************************************
	 */

	/**
	 * @param string $string
	 * @return string
	 */
	protected function getCacheKeyByString($string)
	{
		return md5((string)$string);
	}

	/**
	 * @param Fluent $resource
	 * @return string
	 */
	protected function getCacheKeyByResource(Fluent $resource)
	{
		return $this->getCacheKeyByString((string)$resource);
	}

	/**
	 * @param string $entityClass
	 * @return string
	 */
	protected function getTagName(string $entityClass = null)
	{
		return isset($entityClass) ? $entityClass : $this->getTableName();
	}

	/**
	 * @param Fluent $resource
	 * @return mixed|null
	 */
	protected function loadFromCache(Fluent $resource)
	{
		return self::CACHE_ACTIVE
			? $this->cache->load($this->getCacheKeyByResource($resource))
			: null;
	}

	/**
	 * Save to second level cache
	 * @param Fluent $resource
	 * @param mixed $result
	 * @param array $dependentEntities
	 */
	protected function saveToChache(Fluent $resource, $result, array $dependentEntities = [])
	{
		if (self::CACHE_ACTIVE) {
			$tags = [];
			foreach ($dependentEntities as $entity) {
				$tags[] = $this->getTagName($entity);
			}

			$this->cache->save($this->getCacheKeyByResource($resource), $result, [
				Cache::TAGS => array_unique(array_merge([$this->getTagName()], $tags)),   //2nd, 2nd/entity
				Cache::EXPIRE => Cache::EXPIRATION,
				Cache::SLIDING => true,
			]);
		}
	}

	/**
	 * @param Fluent $resource
	 * @param $callback Callback
	 * @param array $dependentEntities
	 * @return mixed
	 */
	protected function handleCache(Fluent $resource, $callback, array $dependentEntities = [])
	{
		$cachedResult = $this->loadFromCache($resource);
		if (!isset($cachedResult)) {
			$result = call_user_func($callback);
			$this->saveToChache($resource, $result, $dependentEntities);
			return $result;
		}

		return $cachedResult;
	}

	/**
	 * Clean second level entity cache
	 * @param string $entity
	 */
	protected function cleanCache($entity = null)
	{
		$this->cache->clean([
			Cache::TAGS => [$this->getTagName($entity)],
		]);
	}

	/**
	 * Clean whole second level cache (for all entities)
	 */
	protected function cleanAllCache()
	{
		$this->cache->clean();
	}

	/**
	 * Fetches result and saves it to the second level cache
	 * @param Fluent $resource
	 * @param array $dependentEntities
	 * @return mixed
	 */
	protected function fetchAll(Fluent $resource, array $dependentEntities = [])
	{
		$callback = function () use ($resource) {
			return $resource->fetchAll();
		};
		return (array)$this->handleCache($resource, $callback, $dependentEntities);
	}

	/**
	 * Fetches result and saves it to the second level cache
	 * @param Fluent $resource
	 * @param array $dependentEntities
	 * @return mixed
	 */
	protected function fetchArray(Fluent $resource, array $dependentEntities = [])
	{
		$callback = function () use ($resource) {
			return $this->getArray($resource->fetchAll());
		};
		return (array)$this->handleCache($resource, $callback, $dependentEntities);
	}

	/**
	 * Fetches result and saves it to the second level cache
	 * @param Fluent $resource
	 * @param array $dependentEntities
	 * @return mixed
	 */
	protected function fetchSingle(Fluent $resource, array $dependentEntities = [])
	{
		$callback = function () use ($resource) {
			return $resource->fetchSingle();
		};
		return $this->handleCache($resource, $callback, $dependentEntities);
	}

	/**
	 * Fetches result and saves it to the second level cache
	 * @param Fluent $resource
	 * @param array $dependentEntities
	 * @return mixed
	 */
	protected function fetch(Fluent $resource, array $dependentEntities = [])
	{
		$callback = function () use ($resource) {
			return $resource->fetch();
		};
		return $this->handleCache($resource, $callback, $dependentEntities);
	}

	/**
	 * @param Fluent $resource
	 * @param string $key
	 * @param string $value
	 * @param array $dependentEntities
	 * @return array
	 */
	protected function fetchPairs(Fluent $resource, $key, $value, array $dependentEntities = [])
	{
		$callback = function () use ($resource, $key, $value) {
			return $resource->fetchPairs($key, $value);
		};
		return (array)$this->handleCache($resource, $callback, $dependentEntities);
	}
}
