<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\Entity\IAggregateMember;
use Sellastica\Entity\Entity\IAggregateRoot;

class AggregateMemberModified extends ManageAggregateDomainEvent
{
	/**
	 * @param \Sellastica\Entity\Entity\IAggregateRoot $aggregateRoot
	 * @param \Sellastica\Entity\Entity\IAggregateMember $aggregateMember
	 */
	public function __construct(IAggregateRoot $aggregateRoot, IAggregateMember $aggregateMember)
	{
		parent::__construct($aggregateRoot, $aggregateMember, ManageAggregateDomainEvent::PERSIST);
	}
}