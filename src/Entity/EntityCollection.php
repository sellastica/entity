<?php
namespace Sellastica\Entity\Entity;

use Sellastica\Core\Model\Collection;

/**
 * @property IEntity[] $items
 * @method EntityCollection filter(callable $function)
 */
class EntityCollection extends Collection
{
	/**
	 * @param IEntity $entity
	 * @return \Sellastica\Core\Model\Collection
	 * @throws \InvalidArgumentException If argument is not IEntity instance
	 */
	public function remove($entity): Collection
	{
		if (!$entity instanceof IEntity) {
			throw new \InvalidArgumentException('Remove method accepts entities only');
		}

		return parent::remove($entity);
	}

	/**
	 * @param int $entityId
	 * @param mixed $default
	 * @return IEntity|mixed
	 */
	public function getEntity(int $entityId, $default = null)
	{
		$result = current(array_filter($this->items, function(IEntity $entity) use ($entityId) {
			return $entity->getId() === $entityId;
		}));
		return $result ?: $default;
	}

	/**
	 * @param int|IEntity $entityOrId
	 * @return bool
	 */
	public function hasEntity($entityOrId): bool
	{
		$this->assertEntityOrId($entityOrId);
		return (bool)$this->getEntity($entityOrId instanceof IEntity ? $entityOrId->getId() : $entityOrId);
	}

	/**
	 * @param string $property
	 * @param $value
	 * @param mixed $default
	 * @return IEntity|mixed
	 */
	public function getBy(string $property, $value, $default = null)
	{
		$result = current(array_filter($this->items, function(IEntity $entity) use ($property, $value) {
			return $entity->{'get' . $property}() === $value;
		}));
		return $result ?: $default;
	}

	/**
	 * @param string $property
	 * @param $value
	 * @return bool
	 */
	public function hasBy(string $property, $value): bool
	{
		return (bool)$this->getBy($property, $value);
	}

	/**
	 * @param $entityOrId
	 * @throws \InvalidArgumentException
	 */
	private function assertEntityOrId($entityOrId)
	{
		if (!$entityOrId instanceof IEntity && !is_int($entityOrId)) {
			throw new \InvalidArgumentException('$entityOrId must be an instance of IEntity or an integer');
		}
	}
}