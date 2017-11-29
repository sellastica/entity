<?php
namespace Sellastica\Entity\Relation;

class RelationManager
{
	/** @var string Table name (in case of database storage) */
	protected $storage;
	/** @var array */
	protected $container = [];
	/** @var bool Use INSERT IGNORE */
	protected $useIgnore = false;


	/**
	 * @param string $storage
	 */
	public function __construct(string $storage)
	{
		$this->storage = $storage;
	}

	/**
	 * @param string $identifier Database column in case of the database storage
	 * @param mixed $value Column value
	 */
	public function addEntity($identifier, $value = null)
	{
		$this->container[] = array($identifier, $value);
	}

	public function clear()
	{
		$this->container = null;
	}

	/**
	 * @return array
	 */
	public function getEntities()
	{
		return $this->container;
	}

	/**
	 * @return string
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * @return array
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @return bool
	 */
	public function shouldUseIgnore()
	{
		return $this->useIgnore;
	}

	public function useIgnore()
	{
		$this->useIgnore = true;
	}
}