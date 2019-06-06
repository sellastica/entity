<?php
namespace Sellastica\Entity\Mapping;

use Nette;
use Sellastica\Entity\Configuration;
use Sellastica\Entity\Entity\EntityCollection;
use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Relation\RelationGetManager;

abstract class RepositoryProxy implements IRepositoryProxy
{
	/** @var Nette\DI\Container */
	private $container;
	/** @var IRepository */
	private $repository;


	/**
	 * {@inheritDoc}
	 */
	public function __construct(
		Nette\DI\Container $container
	)
	{
		$this->container = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRepository()
	{
		if (!isset($this->repository)) {
			$this->repository = $this->container->getService(
				lcfirst(str_replace('Proxy', '', Nette\Utils\Strings::after(get_called_class(), '\\', -1)))
			);
		}

		return $this->repository;
	}

	/**
	 * @return \Sellastica\Entity\Entity\EntityCollection
	 */
	public function getEmptyCollection(): EntityCollection
	{
		return $this->getRepository()->getEmptyCollection();
	}

	/**
	 * {@inheritDoc}
	 */
	public function nextIdentity()
	{
		return $this->getRepository()->nextIdentity();
	}

	/**
	 * {@inheritDoc}
	 */
	public function find($id = null): ?IEntity
	{
		return $this->getRepository()->find($id);
	}

	/**
	 * @param $id
	 * @param array $fields
	 * @return array|null
	 */
	public function findFields($id, array $fields)
	{
		return $this->getRepository()->findFields($id, $fields);
	}

	/**
	 * @param string $field
	 * @param array $filterValues
	 * @param Configuration|null $configuration
	 * @return array
	 */
	public function findFieldBy(string $field, array $filterValues, Configuration $configuration = null): array
	{
		return $this->getRepository()->findFieldBy($field, $filterValues, $configuration);
	}

	/**
	 * @param $id
	 * @param string $field
	 * @return mixed|false
	 */
	public function findField($id, string $field)
	{
		return $this->getRepository()->findField($id, $field);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findByIds(
		array $idsArray,
		Configuration $configuration = null
	): EntityCollection
	{
		return $this->getRepository()->findByIds($idsArray, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAll(Configuration $configuration = null): EntityCollection
	{
		return $this->getRepository()->findAll($configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findBy(
		array $filterValues,
		Configuration $configuration = null
	): EntityCollection
	{
		return $this->getRepository()->findBy($filterValues, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findByConditions($conditions, Configuration $configuration = null): EntityCollection
	{
		return $this->getRepository()->findByConditions($conditions, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findIn(
		string $column,
		array $values,
		string $modifier = 's',
		Configuration $configuration = null
	): EntityCollection
	{
		return $this->getRepository()->findIn($column, $values, $modifier, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists($id): bool
	{
		return $this->getRepository()->exists($id);
	}

	/**
	 * {@inheritDoc}
	 */
	public function existsBy(array $filterValues): bool
	{
		return $this->getRepository()->existsBy($filterValues);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOneBy(array $filterValues, Configuration $configuration = null): ?IEntity
	{
		return $this->getRepository()->findOneBy($filterValues, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findCount(): int
	{
		return $this->getRepository()->findCount();
	}

	/**
	 * {@inheritDoc}
	 */
	public function findCountBy(array $filterValues): int
	{
		return $this->getRepository()->findCountBy($filterValues);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findPairs(
		string $key = null,
		string $value,
		array $filterValues = [],
		Configuration $configuration = null
	): array
	{
		return $this->getRepository()->findPairs($key, $value, $filterValues, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelationIds(RelationGetManager $relationGetManager): array
	{
		return $this->getRepository()->getRelationIds($relationGetManager);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelationId(RelationGetManager $relationGetManager)
	{
		return $this->getRepository()->getRelationId($relationGetManager);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelations(RelationGetManager $relationGetManager): array
	{
		return $this->getRepository()->getRelations($relationGetManager);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelation(RelationGetManager $relationGetManager)
	{
		return $this->getRepository()->getRelation($relationGetManager);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findPublishable($id = null)
	{
		return $this->getRepository()->findPublishable($id);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAllPublishable(Configuration $configuration = null): EntityCollection
	{
		return $this->getRepository()->findAllPublishable($configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findOnePublishableBy(array $filterValues)
	{
		return $this->getRepository()->findOnePublishableBy($filterValues);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findPublishableBy(
		array $filterValues,
		Configuration $configuration = null
	)
	{
		return $this->getRepository()->findPublishableBy($filterValues, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findCountOfPublishableBy(array $filterValues): int
	{
		return $this->getRepository()->findCountOfPublishableBy($filterValues);
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
		return $this->getRepository()->findSlugs($slugWithoutNumbers, $column, $id, $groupConditions, $slugNumberDivider);
	}

	/**
	 * @param int $entityId
	 * @param array $columns
	 */
	public function saveUncachedColumns(int $entityId, array $columns)
	{
		$this->getRepository()->saveUncachedColumns($entityId, $columns);
	}

	/**
	 * @param array $filter
	 * @param array $data
	 */
	public function updateMany(array $filter, array $data): void
	{
		$this->getRepository()->updateMany($filter, $data);
	}
}
