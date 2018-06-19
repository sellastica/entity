<?php
namespace Sellastica\Entity;

trait TBuilder
{
	/** @var int */
	private $id;

	/**
	 * @return int|null
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param $id
	 * @return $this
	 */
	public function id($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$array = [];
		foreach ($this as $property => $value) {
			$array[$property] = $value;
		}

		return $array;
	}

	/**
	 * @param \Traversable $data
	 * @return $this
	 */
	public function hydrate(\Traversable $data)
	{
		foreach ($data as $property => $value) {
			if (method_exists($this, $property)
				&& !isset($this->$property)) {
				$this->$property($value);
			}
		}

		return $this;
	}
}