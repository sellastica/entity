<?php
namespace Sellastica\Entity\Mapping;

use Dibi;
use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Relation\ManyToManyRelation;
use Sellastica\Entity\Relation\RelationGetManager;

interface IMapper
{
	/**
	 * @return int
	 */
	function nextIdentity(): int;

	/**
	 * @param int $id
	 * @return Dibi\Row|null
	 */
	function find(int $id);

	/**
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 */
	function findFields(int $id, array $fields);

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return array
	 */
	function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array;

	/**
	 * @param int $id
	 * @param string $field
	 * @return mixed|false
	 */
	function findField(int $id, string $field);

	/**
	 * @param array $idsArray
	 * @param Configuration $configuration
	 * @return array
	 */
	function findByIds(array $idsArray, Configuration $configuration = null): array;

	/**
	 * @param Configuration $configuration
	 * @return iterable
	 */
	function findAllIds(Configuration $configuration = null): iterable;

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return array
	 */
	function findBy(array $filterValues, Configuration $configuration = null): array;

	/**
	 * @param \Sellastica\Entity\Entity\ConditionCollection $conditions
	 * @param \Sellastica\Entity\Configuration $configuration
	 * @param \Dibi\Fluent $resource
	 * @return array
	 */
	public function findByConditions(
		\Sellastica\Entity\Entity\ConditionCollection $conditions,
		Configuration $configuration = null,
		Dibi\Fluent $resource = null
	): array;

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $modifier
	 * @param Configuration $configuration
	 * @return array
	 */
	public function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null
	): array;

	/**
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return false|int
	 */
	function findOneBy(array $filterValues, Configuration $configuration = null);

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
	 * @param RelationGetManager $relationGetManager
	 * @return mixed
	 */
	function getRelationId(RelationGetManager $relationGetManager);

	/**
	 * @param RelationGetManager $relationGetManager
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
	 * @param int $id
	 * @return int|null
	 */
	function findPublishable(int $id);

	/**
	 * @param Configuration $configuration
	 * @return array
	 */
	function findAllPublishableIds(Configuration $configuration = null): array;

	/**
	 * @param array $filterValues
	 * @return int|false
	 */
	function findOnePublishableBy(array $filterValues);

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return array
	 */
	function findPublishableBy(array $filterValues, Configuration $configuration = null): array;

	/**
	 * @param array $filterValues
	 * @return int
	 */
	function findCountOfPublishableBy(array $filterValues): int;

	/**
	 * @param array $ids
	 * @return array
	 */
	function getEntitiesByIds(array $ids): array;

	/**
	 * @param IEntity $entity
	 * @return void
	 */
	function insert(IEntity $entity);

	/**
	 * @param IEntity[] $entities
	 */
	function batchInsert(array $entities): void;

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @return void
	 */
	function update(IEntity $entity);

	/**
	 * @param int $entityId
	 * @param array $columns
	 * @return
	 */
	function saveUncachedColumns(int $entityId, array $columns);

	/**
	 * @param int $entityId
	 */
	function deleteById(int $entityId);

	/**
	 * @return void
	 */
	function deleteAll();

	/**
	 * @param string $slugWithoutNumbers
	 * @param string $column
	 * @param int $id
	 * @param array $groupConditions
	 * @param string $slugNumberDivider
	 * @return array
	 */
	function findSlugs(
		string $slugWithoutNumbers,
		string $column = 'slug',
		int $id = null,
		array $groupConditions = [],
		string $slugNumberDivider = '-'
	): array;
}