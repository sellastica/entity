<?php
namespace Sellastica\Entity;

class SorterRule
{
	const ASCENDING = 'ASC',
		DESCENDING = 'DESC';

	/** @var string */
	private $column;
	/** @var bool */
	private $ascending;


	/**
	 * @param string $column
	 * @param bool $ascending
	 */
	public function __construct(string $column, bool $ascending)
	{
		$this->column = $column;
		$this->ascending = $ascending;
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
	public function setColumn(string $column): void
	{
		$this->column = $column;
	}

	/**
	 * @return bool
	 */
	public function isAscending(): bool
	{
		return $this->ascending;
	}

	/**
	 * @param bool $ascending
	 */
	public function setAscending(bool $ascending): void
	{
		$this->ascending = $ascending;
	}

	/**
	 * @return string
	 */
	public function getDirection(): string
	{
		return $this->ascending ? self::ASCENDING : self::DESCENDING;
	}
}