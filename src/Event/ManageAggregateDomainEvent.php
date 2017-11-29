<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\Entity\IAggregateMember;
use Sellastica\Entity\Entity\IAggregateRoot;

abstract class ManageAggregateDomainEvent implements IDomainEvent
{
	const PERSIST = 1,
		REMOVE = 2;

	/** @var \Sellastica\Entity\Entity\IAggregateRoot */
	private $aggregateRoot;
	/** @var \Sellastica\Entity\Entity\IAggregateMember */
	private $aggregateMember;
	/** @var int; */
	private $command;


	/**
	 * @param IAggregateRoot $aggregateRoot
	 * @param \Sellastica\Entity\Entity\IAggregateMember $aggregateMember
	 * @param int $command
	 * @throws \InvalidArgumentException
	 */
	public function __construct(IAggregateRoot $aggregateRoot, IAggregateMember $aggregateMember, int $command)
	{
		if (!in_array($command, [self::PERSIST, self::REMOVE])) {
			throw new \InvalidArgumentException("Unknown command $command");
		}

		$this->aggregateRoot = $aggregateRoot;
		$this->aggregateMember = $aggregateMember;
		$this->command = $command;
	}

	/**
	 * @return IAggregateRoot
	 */
	public function getAggregateRoot(): IAggregateRoot
	{
		return $this->aggregateRoot;
	}

	/**
	 * @return IAggregateMember
	 */
	public function getAggregateMember(): IAggregateMember
	{
		return $this->aggregateMember;
	}

	/**
	 * @return bool
	 */
	public function shouldPersist(): bool
	{
		return $this->command === self::PERSIST;
	}

	/**
	 * @return bool
	 */
	public function shouldRemove(): bool
	{
		return $this->command === self::REMOVE;
	}
}