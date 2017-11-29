<?php
namespace Sellastica\Entity\Entity;

use Sellastica\Entity\EntityManager;
use Sellastica\Entity\Event\IDomainEventPublisher;
use Sellastica\Entity\IBuilder;

abstract class EntityFactory
{
	/** @var EntityManager */
	protected $em;
	/** @var \Sellastica\Entity\Event\IDomainEventPublisher */
	protected $eventPublisher;


	/**
	 * @param EntityManager $em
	 * @param \Sellastica\Entity\Event\IDomainEventPublisher $eventPublisher
	 */
	public function __construct(
		EntityManager $em,
		IDomainEventPublisher $eventPublisher
	)
	{
		$this->em = $em;
		$this->eventPublisher = $eventPublisher;
	}

	/**
	 * @return string Class name including namespace
	 */
	abstract public function getEntityClass(): string;

	/**
	 * @param \Sellastica\Entity\IBuilder $builder
	 * @param bool $initialize
	 * @param int|null $assignedId
	 * @return IEntity
	 */
	public function build(IBuilder $builder, bool $initialize = true, int $assignedId = null): IEntity
	{
		if (!$builder->getId()) {
			$entityState = EntityState::new();
			if (isset($assignedId)) {
				$builder->id($assignedId);
			} elseif ($builder->generateId()) {
				$builder->id($this->em->getRepository(static::getEntityClass())->nextIdentity());
			}
		} else {
			$entityState = EntityState::persisted();
		}

		$entity = $builder->build();
		$entity->getEntityMetadata()->setState($entityState);
		if (true === $initialize) {
			$this->initialize($entity);
		}

		return $entity;
	}

	/**
	 * @param IEntity $entity
	 * @param $first
	 * @param $second
	 */
	final public function initialize(IEntity $entity, $first = null, $second = null)
	{
		if ($entity->getEntityMetadata()->isInitialized()) {
			return;
		}

		$entity->setEventPublisher($this->eventPublisher);
		//initialize relations
		$this->doInitialize($entity);
		//metadata initialization (needs to be after item initialization)
		$metadata = $entity->getEntityMetadata();
		$metadata->initialize();
		if (!$metadata->getState()->isNew()) {
			$metadata->setOriginalData($entity->toArray());
		}
	}

	/**
	 * @param IEntity $entity
	 */
	abstract protected function doInitialize(IEntity $entity);
}
