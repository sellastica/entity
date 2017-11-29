<?php
namespace Sellastica\Entity\Entity;

class EntityState
{
	const STATE_NEW = 'new',   //not stored in the persistence storage
		STATE_PERSISTED = 'persisted';   //stored in the persistence storage

	/** @var string */
	private $state;

	/**
	 * @param string $state
	 * @throws \InvalidArgumentException
	 */
	public function __construct(string $state)
	{
		if ($state !== self::STATE_NEW
			&& $state !== self::STATE_PERSISTED) {
			throw new \InvalidArgumentException("Invalid state $state");
		}

		$this->state = $state;
	}

	/**
	 * @return string
	 */
	public function getState(): string
	{
		return $this->state;
	}

	/**
	 * @return bool
	 */
	public function isNew(): bool
	{
		return $this->state === self::STATE_NEW;
	}

	/**
	 * @return bool
	 */
	public function isPersisted()
	{
		return $this->state === self::STATE_PERSISTED;
	}

	/**
	 * @return EntityState
	 */
	public static function new()
	{
		return new self(self::STATE_NEW);
	}

	/**
	 * @return EntityState
	 */
	public static function persisted()
	{
		return new self(self::STATE_PERSISTED);
	}
}