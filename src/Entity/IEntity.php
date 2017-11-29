<?php
namespace Sellastica\Entity\Entity;

use Sellastica\Entity\Event\IDomainEventPublisher;
use Sellastica\Entity\Relation\IEntityRelations;

/**
 * @property array $onSave
 * @property array $onRemove
 */
interface IEntity
{
	const FLAG_REMOVE = 1;

	/**
	 * @return int
	 */
	function getId();

	/**
	 * @param IEntityRelations $relationService
	 */
	function setRelationService(IEntityRelations $relationService);

	/**
	 * @param IDomainEventPublisher $eventPublisher
	 */
	function setEventPublisher(IDomainEventPublisher $eventPublisher);

	/**
	 * Local entity is from the local storage
	 * This method solves possible conflicts in local entity IDs and "remote" entity IDs
	 * @return bool
	 */
	static function isIdGeneratedByStorage(): bool;

	/**
	 * @return EntityMetadata
	 */
	function getEntityMetadata(): EntityMetadata;

	/**
	 * @return array
	 */
	function getChangedData(): array;

	/**
	 * @return bool
	 */
	function isChanged(): bool;

	/**
	 * @return void
	 */
	function updateOriginalData();

	/**
	 * @return string
	 */
	static function getShortName();

	/**
	 * @param $flag
	 */
	function setFlag($flag);

	/**
	 * @param $flag
	 */
	function removeFlag($flag);

	/**
	 * @param $flag
	 * @return bool
	 */
	function hasFlag($flag): bool;

	/**
	 * @return bool
	 */
	function shouldPersist(): bool;

	/**
	 * @return bool
	 */
	function shouldRemove(): bool;

	/**
	 * @return array
	 */
	function toArray();
}