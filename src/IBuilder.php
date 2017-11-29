<?php
namespace Sellastica\Entity;

use Sellastica\Entity\Entity\IEntity;

interface IBuilder
{
	/**
	 * @return int|null
	 */
	function getId();

	/**
	 * @param int $id
	 */
	function id(int $id);

	/**
	 * @return bool
	 */
	function generateId(): bool;

	/**
	 * @return IEntity
	 */
	function build();

	/**
	 * @return array
	 */
	function toArray(): array;
}