<?php
namespace Sellastica\Entity\Entity;

interface IAggregateMember extends IEntity
{
	/**
	 * @return int
	 */
	function getAggregateId(): int;

	/**
	 * @return string
	 */
	function getAggregateRootClass(): string;

	/**
	 * @return IAggregateRoot|null
	 */
	function getAggregateRoot();
}