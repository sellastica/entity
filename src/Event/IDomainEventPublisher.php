<?php
namespace Sellastica\Entity\Event;

interface IDomainEventPublisher
{
	/**
	 * @param IDomainEventSubscriber $subscriber
	 */
	function subscribe(IDomainEventSubscriber $subscriber);

	/**
	 * @param IDomainEvent $event
	 */
	function publish(IDomainEvent $event);
}