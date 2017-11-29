<?php
namespace Sellastica\Entity;

trait TModifier
{
	/**
	 * @param array $data
	 */
	private function __construct($data)
	{
		$this->hydrate($data);
	}

	/**
	 * @param $data
	 * @return $this
	 */
	public function hydrate($data)
	{
		foreach ($data as $property => $value) {
			if (method_exists($this, $property)) {
				$this->$property($value);
			}
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 * @return TModifier|static
	 */
	public static function create($data = []): self
	{
		return new self($data);
	}
}