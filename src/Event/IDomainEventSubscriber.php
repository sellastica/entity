<?php
namespace Sellastica\Entity\Event;

interface IDomainEventSubscriber
{
	/**
	 * @param IDomainEvent $event
	 */
	function handle(IDomainEvent $event);

	/**
	 * @param IDomainEvent $event
	 * @return bool
	 */
	function isSubscribedTo(IDomainEvent $event): bool;
}