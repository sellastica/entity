<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\Entity\IAggregateMember;
use Sellastica\Entity\Entity\IAggregateRoot;

class AggregateMemberRemoved extends ManageAggregateDomainEvent
{
	/**
	 * @param IAggregateRoot $aggregateRoot
	 * @param \Sellastica\Entity\Entity\IAggregateMember $aggregateMember
	 */
	public function __construct(IAggregateRoot $aggregateRoot, IAggregateMember $aggregateMember)
	{
		parent::__construct($aggregateRoot, $aggregateMember, ManageAggregateDomainEvent::REMOVE);
	}
}