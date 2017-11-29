<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\Entity\IAggregateMember;
use Sellastica\Entity\Entity\IEntity;

abstract class ManageEntityDomainEvent implements IDomainEvent
{
	const PERSIST = 1,
		REMOVE = 2;

	/** @var IEntity */
	private $entity;
	/** @var int; */
	private $command;


	/**
	 * @param IEntity $entity
	 * @param int $command
	 * @throws \InvalidArgumentException
	 */
	public function __construct(IEntity $entity, int $command)
	{
		if (!in_array($command, [self::PERSIST, self::REMOVE])) {
			throw new \InvalidArgumentException("Unknown command $command");
		} elseif ($entity instanceof IAggregateMember) {
			throw new \InvalidArgumentException('
				Aggregate member must be managed inside its aggregate root. 
				You can use events AggregateMemberAdded/AggregateMemberModified instead
			');
		}

		$this->entity = $entity;
		$this->command = $command;
	}

	/**
	 * @return IEntity
	 */
	public function getEntity()
	{
		return $this->entity;
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