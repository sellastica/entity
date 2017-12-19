<?php
namespace Sellastica\Entity\Relation;

use Sellastica\Entity\Entity\IEntity;
use Sellastica\Utils\Strings;

abstract class ManyToManyRelation
{
	const PERSIST = 1,
		REMOVE = 2;

	/**
	 * @return IEntity
	 */
	abstract public function getEntity(): IEntity;

	/**
	 * @return IEntity
	 */
	abstract public function getRelatedEntity(): IEntity;

	/**
	 * @return int
	 */
	abstract protected function getCommand(): int;

	/**
	 * @return array
	 */
	abstract public function toArray(): array;

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return implode('_', $this->toArray());
	}

	/**
	 * @return bool
	 */
	public function shouldPersist(): bool
	{
		return $this->getCommand() === self::PERSIST;
	}

	/**
	 * @return bool
	 */
	public function shouldRemove(): bool
	{
		return $this->getCommand() === self::REMOVE;
	}

	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		$tableName = Strings::after(get_class($this), '\\', -1);
		$tableName = Strings::fromCamelCase($tableName);
		return str_replace('_relation', '_rel', $tableName);
	}

	/**
	 * @return bool
	 */
	public function isCrmDatabase(): bool
	{
		return false;
	}
}