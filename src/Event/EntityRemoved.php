<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\Entity\IEntity;

class EntityRemoved extends ManageEntityDomainEvent
{
	/**
	 * @param \Sellastica\Entity\Entity\IEntity $entity
	 */
	public function __construct(IEntity $entity)
	{
		parent::__construct($entity, ManageEntityDomainEvent::REMOVE);
	}
}