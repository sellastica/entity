<?php
namespace Sellastica\Entity\Entity;

class EntityMetadata
{
	/** @var \DateTime|null */
	private $created;
	/** @var \DateTime|null */
	private $modified;
	/** @var EntityState Entity state */
	private $state;
	/** @var bool */
	private $initialized = false;
	/** @var array */
	private $originalData = [];


	/**
	 * @param EntityState $state
	 */
	public function __construct(EntityState $state)
	{
		$this->state = $state;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getCreated(): ?\DateTime
	{
		return $this->created;
	}

	/**
	 * @param \DateTime $created
	 */
	public function setCreated(\DateTime $created = null)
	{
		$this->created = $created ?? new \DateTime();
	}

	/**
	 * @return \DateTime|null
	 */
	public function getModified(): ?\DateTime
	{
		return $this->modified;
	}

	/**
	 * @param \DateTime $modified
	 */
	public function setModified(\DateTime $modified = null)
	{
		$this->modified = $modified ?? new \DateTime();
	}

	/**
	 * @return EntityState
	 */
	public function getState(): EntityState
	{
		return $this->state;
	}

	/**
	 * @param EntityState $state
	 */
	public function setState(EntityState $state)
	{
		$this->state = $state;
	}

	/**
	 * @return array
	 */
	public function getOriginalData(): array
	{
		return $this->originalData;
	}

	/**
	 * @param array $originalData
	 */
	public function setOriginalData(array $originalData)
	{
		//add modified to the data, so we can handle modified attribute changes
		$this->originalData = array_merge($originalData, ['modified' => $this->getModified()]);
	}

	public function initialize()
	{
		$this->initialized = true;
	}

	/**
	 * @return bool
	 */
	public function isInitialized(): bool
	{
		return $this->initialized;
	}
}