<?php
namespace Sellastica\Entity\Entity;

class ConditionCollection implements \IteratorAggregate, \ArrayAccess
{
	const PUBLISHABLE = 1,
		OR = 2;

	/** @var \Sellastica\Entity\Entity\Condition[] */
	private $conditions = [];
	/** @var array */
	private $flags = [];


	/**
	 * @param \Sellastica\Entity\Entity\Condition[] $conditions
	 */
	public function __construct(array $conditions = [])
	{
		$this->conditions = $conditions;
	}

	/**
	 * @param $flag
	 */
	public function setFlag($flag): void
	{
		$this->flags[$flag] = true;
	}

	/**
	 * @param $flag
	 * @return bool
	 */
	public function hasFlag($flag): bool
	{
		return isset($this->flags[$flag]);
	}

	/**
	 * @param $flag
	 */
	public function removeFlag($flag): void
	{
		if ($this->hasFlag($flag)) {
			unset($this->flags[$flag]);
		}
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->conditions);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		return isset($this->conditions[$offset]);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		return isset($this->conditions[$offset]) ? $this->conditions[$offset] : null;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		if ($offset !== null) {
			$this->conditions[$offset] = $value;
		} else {
			$this->conditions[] = $value;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		throw new \Nette\NotImplementedException();
	}
}