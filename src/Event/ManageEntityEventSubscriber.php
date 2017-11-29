<?php
namespace Sellastica\Entity\Event;

use Sellastica\Entity\EntityManager;

class ManageEntityEventSubscriber implements IDomainEventSubscriber
{
	/** @var EntityManager */
	private $entityManager;

	/**
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param IDomainEvent $event
	 */
	public function handle(IDomainEvent $event)
	{
		if ($event instanceof ManageMNRelationDomainEvent) {
			//M:N relations
			$this->entityManager->addRelation($event->getAffectedEntity(), $event->getManyToManyRelation());
		} elseif ($event instanceof ManageAggregateDomainEvent) {
			if ($event->shouldPersist()) {
				$this->entityManager->persist($event->getAggregateRoot());
				$this->entityManager->persist($event->getAggregateMember());
			} elseif ($event->shouldRemove()) {
				$this->entityManager->remove($event->getAggregateMember());
			}
		} elseif ($event instanceof ManageEntityDomainEvent) {
			//entity without any relation to some other entity (it has been just created in another independent entity)
			if ($event->shouldPersist()) {
				$this->entityManager->persist($event->getEntity());
			} elseif ($event->shouldRemove()) {
				$this->entityManager->remove($event->getEntity());
			}
		}
	}

	/**
	 * @param IDomainEvent $event
	 * @return bool
	 */
	public function isSubscribedTo(IDomainEvent $event): bool
	{
		return $event instanceof ManageMNRelationDomainEvent
			|| $event instanceof ManageAggregateDomainEvent
			|| $event instanceof ManageEntityDomainEvent;
	}
}