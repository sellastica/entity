<?php
namespace Sellastica\Entity;

class Reflections
{
	/** @var \Nette\Reflection\ClassType[] */
	private $reflections = [];


	/**
	 * @param Entity\IEntity $entity
	 * @return \Nette\Reflection\ClassType
	 */
	public function getReflection(\Sellastica\Entity\Entity\IEntity $entity): \Nette\Reflection\ClassType
	{
		$class = get_class($entity);
		if (!isset($this->reflections[$class])) {
			$this->reflections[$class] = new \Nette\Reflection\ClassType($entity);
		}

		return $this->reflections[$class];
	}
}