<?php
namespace Sellastica\Entity;

class UnitOfWork
{
	/** @var array */
	private $entities = [];
	/** @var array */
	private $relations = [];


	/**
	 * @param callable $function
	 * @return \Sellastica\Entity\Entity\IEntity[]
	 */
	public function getEntitiesBy(callable $function): array
	{
		$return = [];
		foreach ($this->entities as $entityType => $entities) {
			foreach ($entities as $key => $entity) {
				if ($this->matchesCallable($entity, $function)) {
					$return[] = $entity;
				}
			}
		}

		return $return;
	}

	/**
	 * @return array
	 */
	public function getAllEntities(): array
	{
		$return = [];
		foreach ($this->entities as $entityType => $entities) {
			foreach ($entities as $key => $entity) {
				$return[] = $entity;
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
			 * @var \Sellastica\Entity\Entity\IEntity $entity
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
			 * @var \Sellastica\Entity\Entity\IEntity $entity
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
	 * @return \Sellastica\Entity\Entity\IEntity[]
	 */
	public function getEntitiesByClassName(string $className): array
	{
		return $this->entities[$className] ?? [];
	}

	/**
	 * @return int
	 */
	public function getEntitiesCount(): int
	{
		$count = 0;
		foreach ($this->entities as $class => $entities) {
			$count += sizeof($entities);
		}

		return $count;
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @param callable $function
	 * @return bool
	 */
	private function matchesCallable(\Sellastica\Entity\Entity\IEntity $entity, callable $function): bool
	{
		return $function($entity);
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	public function remove(\Sellastica\Entity\Entity\IEntity $entity)
	{
		$this->attach($entity);
		$entity->setFlag(\Sellastica\Entity\Entity\IEntity::FLAG_REMOVE);
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	private function unremove(\Sellastica\Entity\Entity\IEntity $entity)
	{
		$entity->removeFlag(\Sellastica\Entity\Entity\IEntity::FLAG_REMOVE);
	}

	/**
	 * Adds entity to entities (loaded from persistence storage) entites
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	public function attach(\Sellastica\Entity\Entity\IEntity $entity)
	{
		$this->detach($entity, false);
		$this->entities[get_class($entity)][$this->getKey($entity)] = $entity;
	}

	/**
	 * Detaches entity from the UoW
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @param bool $detachDependencies
	 */
	public function detach(\Sellastica\Entity\Entity\IEntity $entity, bool $detachDependencies = true)
	{
		$this->unremove($entity);
		$this->uncache($entity);
		if (!$detachDependencies) {
			return;
		}

		$this->removeRelationByEntity($entity);
		//remove dependent aggregate members
		if ($entity instanceof \Sellastica\Entity\Entity\IAggregateRoot) {
			$aggregateMembers = $this->getEntitiesBy(function (\Sellastica\Entity\Entity\IEntity $member) use ($entity) {
				return $member instanceof \Sellastica\Entity\Entity\IAggregateMember
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
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	private function uncache(\Sellastica\Entity\Entity\IEntity $entity)
	{
		if (isset($this->entities[get_class($entity)][$this->getKey($entity)])) {
			unset($this->entities[get_class($entity)][$this->getKey($entity)]);
		}
	}

	/**
	 * Loads entity from UoW, searches in all states
	 * @param int $id
	 * @param string $class
	 * @return \Sellastica\Entity\Entity\IEntity|null
	 */
	public function load(int $id, string $class): ?\Sellastica\Entity\Entity\IEntity
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
			$this->entities = [];
			$this->relations = [];
		}
	}

	public function clearRelations(): void
	{
		$this->relations = [];
	}

	/**
	 * @return array
	 */
	public function getRelations(): array
	{
		return $this->relations;
	}

	/**
	 * @param \Sellastica\Entity\Relation\ManyToManyRelation $relation
	 */
	public function addRelation(\Sellastica\Entity\Relation\ManyToManyRelation $relation)
	{
		$this->relations[get_class($relation->getEntity())][] = $relation;
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	public function removeRelationByEntity(\Sellastica\Entity\Entity\IEntity $entity)
	{
		foreach ($this->relations as $className => $relations) {
			foreach ($relations as $key => $relation) {
				/** @var \Sellastica\Entity\Relation\ManyToManyRelation $relation */
				if ($relation->getEntity() instanceof $entity
					|| $relation->getRelatedEntity() instanceof $entity) {
					unset($this->relations[$className][$key]);
				}
			}
		}
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @return bool
	 */
	public function isCached(\Sellastica\Entity\Entity\IEntity $entity): bool
	{
		return isset($this->entities[get_class($entity)][$this->getKey($entity)]);
	}

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 * @return string
	 */
	private function getKey(\Sellastica\Entity\Entity\IEntity $entity): string
	{
		return $entity->getId() ?? spl_object_hash($entity);
	}
}