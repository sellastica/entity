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
	 * {@inheritDoc}
	 */
	public function nextIdentity(): int
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
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 */
	public function findFields(int $id, array $fields)
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
	 * @param int $id
	 * @param string $field
	 * @return mixed|false
	 */
	public function findField(int $id, string $field)
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
	): EntityCollection
	{
		return $this->getRepository()->findIn($column, $values, $modifier, $configuration);
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists(int $id): bool
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
		$key,
		$value,
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
	public function findPublishable(int $id = null)
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
}
