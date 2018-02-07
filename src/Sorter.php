<?php
namespace Sellastica\Entity;

class Sorter
{
	const ASC = 'asc',
		DESC = 'desc';

	/** @var SorterRule[] */
	private $rules = [];


	/**
	 * @param $column
	 * @param bool $isAscending
	 */
	public function addRule($column, $isAscending = TRUE)
	{
		$this->rules[] = new SorterRule($column, $isAscending);
	}

	/**
	 * @return SorterRule[]
	 */
	public function getRules(): array
	{
		return $this->rules;
	}

	/**
	 * @param string $column
	 * @return SorterRule|null
	 */
	public function getRule(string $column): ?SorterRule
	{
		foreach ($this->rules as $rule) {
			if ($rule->getColumn() === $column) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function isSortedBy(string $column): bool
	{
		return (bool)$this->getRule($column);
	}

	/**
	 * @param string $column
	 */
	public function removeRule(string $column): void
	{
		foreach ($this->rules as $key => $rule) {
			if ($rule->getColumn() === $column) {
				unset($this->rules[$key]);
			}
		}
	}
}