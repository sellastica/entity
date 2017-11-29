<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\Entity\IEntity;
use Sellastica\Entity\Relation\ManyToManyRelation;

abstract class ManageMNRelationDomainEvent implements IDomainEvent
{
	/** @var \Sellastica\Entity\Entity\IEntity */
	private $affectedEntity;
	/** @var \Sellastica\Entity\Entity\IEntity */
	private $affector;

	/**
	 * @param \Sellastica\Entity\Entity\IEntity $affectedEntity
	 * @param \Sellastica\Entity\Entity\IEntity $affector
	 */
	public function __construct(IEntity $affectedEntity, IEntity $affector)
	{
		$this->affectedEntity = $affectedEntity;
		$this->affector = $affector;
	}

	/**
	 * @return \Sellastica\Entity\Entity\IEntity
	 */
	public function getAffectedEntity(): IEntity
	{
		return $this->affectedEntity;
	}

	/**
	 * @return \Sellastica\Entity\Entity\IEntity
	 */
	public function getAffector(): IEntity
	{
		return $this->affector;
	}

	/**
	 * @return \Sellastica\Entity\Relation\ManyToManyRelation
	 */
	abstract public function getManyToManyRelation(): ManyToManyRelation;
}