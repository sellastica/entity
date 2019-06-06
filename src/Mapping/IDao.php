<?php
namespace Sellastica\Entity\Mapping;

use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\EntityCollection;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Relation\ManyToManyRelation;
use Sellastica\Entity\Relation\RelationGetManager;

interface IDao
{
	/**
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	function getEmptyCollection(): EntityCollection;

	/**
	 * @return mixed
	 */
	function nextIdentity();

	/**
	 * @param $id
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	function find($id);

	/**
	 * @param $id
	 * @param array $fields
	 * @return array|null
	 */
	function findFields($id, array $fields);

	/**
	 * @param $id
	 * @param string $field
	 * @return mixed|false
	 */
	function findField($id, string $field);

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return array
	 */
	function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array;

	/**
	 * @param array $idsArray
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findByIds(array $idsArray, Configuration $configuration = null): EntityCollection;

	/**
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findAll(Configuration $configuration = null): EntityCollection;

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findBy(array $filterValues, Configuration $configuration = null): EntityCollection;

	/**
	 * @param \Sellastica\Entity\Entity\ConditionCollection $conditions
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findByConditions(
		\Sellastica\Entity\Entity\ConditionCollection $conditions,
		Configuration $configuration = null
	): EntityCollection;

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $modifier
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	public function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null
	): EntityCollection;

	/**
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	function findOneBy(array $filterValues, Configuration $configuration = null): ?IEntity;

	/**
	 * @return int
	 */
	function findCount(): int;

	/**
	 * @param array $filterValues
	 * @return int
	 */
	function findCountBy(array $filterValues): int;

	/**
	 * @param string|null $key
	 * @param string $value
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return array
	 */
	function findPairs(
		string $key = null,
		string $value,
		array $filterValues = [],
		Configuration $configuration = null
	): array;

	/**
	 * @param $id
	 * @return bool
	 */
	function exists($id): bool;

	/**
	 * @param array $filterValues
	 * @return bool
	 */
	function existsBy(array $filterValues): bool;

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array
	 */
	function getRelationIds(RelationGetManager $relationGetManager): array;

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return mixed
	 */
	function getRelationId(RelationGetManager $relationGetManager);

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array
	 */
	function getRelations(RelationGetManager $relationGetManager): array;

	/**
	 * @param \Sellastica\Entity\Relation\RelationGetManager $relationGetManager
	 * @return array|null
	 */
	function getRelation(RelationGetManager $relationGetManager);

	/**
	 * @param ManyToManyRelation $relation
	 */
	function addRelation(ManyToManyRelation $relation);

	/**
	 * @param ManyToManyRelation $relation
	 */
	function removeRelation(ManyToManyRelation $relation);

	/**
	 * @param $id
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	function findPublishable($id);

	/**
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findAllPublishable(Configuration $configuration = null): EntityCollection;

	/**
	 * @param array $filterValues
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	function findOnePublishableBy(array $filterValues);

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return EntityCollection|array
	 */
	function findPublishableBy(array $filterValues, Configuration $configuration = null);

	/**
	 * @param array $filterValues
	 * @return int
	 */
	function findCountOfPublishableBy(array $filterValues): int;

	/**
	 * @param IEntity $entity
	 * @return void
	 */
	function save(IEntity $entity);

	/**
	 * @param \Sellastica\Entity\Entity\IEntity[] $entities
	 */
	function batchInsert(array $entities): void;

	/**
	 * @param array $filter
	 * @param array $data
	 */
	function updateMany(array $filter, array $data): void;

	/**
	 * @param int $entityId
	 */
	function deleteById($entityId);

	/**
	 * @return void
	 */
	function deleteAll();

	/**
	 * @param string $slugWithoutNumbers
	 * @param string $column
	 * @param $id
	 * @param array $groupConditions
	 * @param string $slugNumberDivider
	 * @return array
	 */
	function findSlugs(
		string $slugWithoutNumbers,
		string $column = 'slug',
		$id = null,
		array $groupConditions = [],
		string $slugNumberDivider = '-'
	): array;

	/**
	 * @param int $entityId
	 * @param array $columns
	 * @return
	 */
	function saveUncachedColumns(int $entityId, array $columns);
}