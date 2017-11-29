<?php
namespace Sellastica\Entity\Entity;

use Nette\SmartObject;
use Sellastica\Entity\IBuilder;
use Sellastica\Entity\IModifier;

trait TAbstractEntity
{
	use SmartObject;

	/**
	 * @param IBuilder $builder
	 */
	private function hydrate(IBuilder $builder)
	{
		foreach ($builder->toArray() as $property => $value) {
			$this->$property = $value;
		}
	}

	/**
	 * Sets entity properties from array
	 * @param IModifier $modifier
	 */
	public function modify(IModifier $modifier)
	{
		foreach ($modifier->toArray() as $property => $value) {
			$setter = 'set' . ucfirst($property);
			$this->{$setter}($value);
		}
	}
}