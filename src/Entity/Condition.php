<?php
namespace Sellastica\Entity\Entity;

class Condition
{
	/** @var string (%i, %s, %iN, %~like~...) */
	private $modifier;
	/** @var string */
	private $key;
	/** @var mixed */
	private $value;
	/** @var string (=, !=, IN, NOT IN, LIKE...) */
	private $comparator;


	/**
	 * @param string $modifier
	 */
	private function __construct(
		string $modifier
	)
	{
		$this->modifier = $modifier;
	}

	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 * @return Condition
	 */
	public function key(string $key): Condition
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModifier(): string
	{
		return $this->modifier;
	}

	/**
	 * @return string
	 */
	public function getComparator(): string
	{
		return $this->comparator;
	}

	/**
	 * @param string $comparator
	 * @return Condition
	 */
	public function comparator(string $comparator): Condition
	{
		$this->comparator = $comparator;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return Condition
	 */
	public function value($value)
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * @param $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function equals($value): Condition
	{
		$this->value = $value;
		$this->comparator = '=';
		return $this;
	}

	/**
	 * @param $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function notEquals($value): Condition
	{
		$this->value = $value;
		$this->comparator = '!=';
		return $this;
	}

	/**
	 * @param $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function greater($value): Condition
	{
		$this->value = $value;
		$this->comparator = '>';
		return $this;
	}

	/**
	 * @param $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function smaller($value): Condition
	{
		$this->value = $value;
		$this->comparator = '<';
		return $this;
	}

	/**
	 * @param $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function greaterOrEquals($value): Condition
	{
		$this->value = $value;
		$this->comparator = '>=';
		return $this;
	}

	/**
	 * @param $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function smallerOrEquals($value): Condition
	{
		$this->value = $value;
		$this->comparator = '<=';
		return $this;
	}

	/**
	 * @param array $values
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function in(array $values): Condition
	{
		$this->value = $values;
		$this->comparator = 'IN';
		return $this;
	}

	/**
	 * @param string $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function like(string $value): Condition
	{
		$this->value = $value;
		$this->comparator = 'LIKE';
		return $this;
	}

	/**
	 * @param string $value
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public function notLike(string $value): Condition
	{
		$this->value = $value;
		$this->comparator = 'NOT LIKE';
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf('[%s] %s %s', $this->key, $this->comparator, $this->modifier);
	}

	/**
	 * @param string|null $alias
	 * @return string
	 */
	public function toString(string $alias = null): string
	{
		return $alias
			? sprintf('[%s.%s] %s %s', $alias, $this->key, $this->comparator, $this->modifier)
			: sprintf('[%s] %s %s', $this->key, $this->comparator, $this->modifier);
	}

	/**
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public static function int(): Condition
	{
		return new self('%i');
	}

	/**
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public static function string(): Condition
	{
		return new self('%s');
	}

	/**
	 * @param string $modifier
	 * @return \Sellastica\Entity\Entity\Condition
	 */
	public static function create(string $modifier): Condition
	{
		return new self($modifier);
	}
}