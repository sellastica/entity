<?php
namespace Sellastica\Entity\Event;

class DomainEventPublisher implements IDomainEventPublisher
{
	/** @var IDomainEventSubscriber[] */
	private $subscribers = [];


	/**
	 * @param IDomainEventSubscriber $subscriber
	 */
	public function subscribe(IDomainEventSubscriber $subscriber)
	{
		$this->subscribers[] = $subscriber;
	}

	/**
	 * @param IDomainEvent $event
	 */
	public function publish(IDomainEvent $event)
	{
		foreach ($this->subscribers as $subscriber) {
			if ($subscriber->isSubscribedTo($event)) {
				$subscriber->handle($event);
			}
		}
	}
}