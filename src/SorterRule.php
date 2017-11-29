<?php
namespace Sellastica\Entity;

class SorterRule
{
	/** @var string */
	private $column;
	/** @var bool */
	private $isAscending;

	const ASCENDING = 'ASC';
	const DESCENDING = 'DESC';


	/**
	 * @param string $column
	 * @param bool $isAscending
	 */
	public function __construct($column, $isAscending)
	{
		$this->column = $column;
		$this->isAscending = (bool)$isAscending;
	}

	/**
	 * @return string
	 */
	public function getColumn(): string
	{
		return $this->column;
	}

	/**
	 * @param string $column
	 */
	public function setColumn(string $column)
	{
		$this->column = $column;
	}

	/**
	 * @return bool
	 */
	public function isAscending(): bool
	{
		return $this->isAscending;
	}

	public function setAscending(): void
	{
		$this->isAscending = true;
	}

	public function setDescending(): void
	{
		$this->isAscending = false;
	}

	/**
	 * @return string
	 */
	public function getDirection(): string
	{
		return $this->isAscending ? self::ASCENDING : self::DESCENDING;
	}
}