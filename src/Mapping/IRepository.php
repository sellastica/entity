<?php
namespace Sellastica\Entity\Mapping;

use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\EntityCollection;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Relation\RelationGetManager;

interface IRepository
{
	/**
	 * @return int
	 */
	function nextIdentity(): int;

	/**
	 * @param int|string $id
	 * @return mixed
	 */
	function find($id): ?IEntity;

	/**
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 */
	function findFields(int $id, array $fields);

	/**
	 * @param int $id
	 * @param string $field
	 * @return mixed|false
	 */
	function findField(int $id, string $field);

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return array
	 */
	function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array;

	/**
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return IEntity|mixed|null
	 */
	function findOneBy(array $filterValues, Configuration $configuration = null): ?IEntity;

	/**
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findAll(Configuration $configuration = null): EntityCollection;

	/**
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return EntityCollection|mixed
	 */
	function findBy(array $filterValues, Configuration $configuration = null): EntityCollection;

	/**
	 * @param string $column
	 * @param array $values
	 * @param string $modifier
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null
	): EntityCollection;

	/**
	 * @param array $idsArray
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findByIds(array $idsArray, Configuration $configuration = null): EntityCollection;

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
	 * @param string $key
	 * @param string $value
	 * @param array $filterValues
	 * @param Configuration $configuration
	 * @return array
	 */
	function findPairs($key, $value, array $filterValues = [], Configuration $configuration = null): array;

	/**
	 * @param int $id
	 * @return bool
	 */
	function exists(int $id): bool;

	/**
	 * @param array $filterValues
	 * @return bool
	 */
	function existsBy(array $filterValues): bool;

	/**
	 * @param RelationGetManager $relationGetManager
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
	 * @param RelationGetManager $relationGetManager
	 * @return array|null
	 */
	function getRelation(RelationGetManager $relationGetManager);

	/**
	 * @param int $id
	 * @return IEntity|null
	 */
	function findPublishable(int $id);

	/**
	 * @param array $filterValues
	 * @return IEntity|null
	 */
	function findOnePublishableBy(array $filterValues);

	/**
	 * @param Configuration $configuration
	 * @return EntityCollection
	 */
	function findAllPublishable(Configuration $configuration = null): EntityCollection;

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

	/**
	 * @param int $entityId
	 * @param array $columns
	 * @return
	 */
	function saveUncachedColumns(int $entityId, array $columns);
}