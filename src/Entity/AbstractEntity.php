<?php
namespace Sellastica\Entity\Entity;

use Nette;
use Sellastica\Entity\Event\IDomainEventPublisher;
use Sellastica\Entity\Relation\IEntityRelations;
use Sellastica\Utils\Arrays;

abstract class AbstractEntity implements IEntity
{
	/** @var array */
	public $onSave = [];
	/** @var array */
	public $onRemove = [];

	/** @var int */
	protected $id;
	/** @var IEntityRelations|null */
	protected $relationService;
	/** @var IDomainEventPublisher */
	protected $eventPublisher;
	/** @var EntityMetadata */
	private $entityMetadata;
	/** @var mixed */
	private $flag;

	/** @var array */
	private $setRelations = [];


	/**
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * @param IEntityRelations $relationService
	 */
	public function setRelationService(IEntityRelations $relationService)
	{
		$this->relationService = $relationService;
	}

	/**
	 * @param IDomainEventPublisher $eventPublisher
	 */
	public function setEventPublisher(IDomainEventPublisher $eventPublisher)
	{
		$this->eventPublisher = $eventPublisher;
	}

	/**
	 * @param $flag
	 * @return bool
	 */
	public function hasFlag($flag): bool
	{
		return $this->flag === $flag;
	}

	/**
	 * @param $flag
	 */
	public function setFlag($flag)
	{
		$this->flag = $flag;
	}

	/**
	 * @param $flag
	 */
	public function removeFlag($flag)
	{
		if ($this->hasFlag($flag)) {
			$this->flag = null;
		}
	}

	/**
	 * @return bool
	 */
	public static function isIdGeneratedByStorage(): bool
	{
		return false;
	}

	/**
	 * @return EntityMetadata
	 */
	public function getEntityMetadata(): EntityMetadata
	{
		if (!isset($this->entityMetadata)) {
			$this->entityMetadata = new EntityMetadata(
				$this->id ? EntityState::persisted() : EntityState::new()
			);
		}

		return $this->entityMetadata;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getCreated(): ?\DateTime
	{
		return $this->getEntityMetadata()->getCreated();
	}

	/**
	 * @param \DateTime $created
	 */
	public function setCreated(\DateTime $created)
	{
		$this->getEntityMetadata()->setCreated($created);
	}

	/**
	 * @return \DateTime|null
	 */
	public function getModified(): ?\DateTime
	{
		return $this->getEntityMetadata()->getModified();
	}

	/**
	 * @param \DateTime $modified
	 */
	public function setModified(\DateTime $modified = null)
	{
		$modified = $modified ?? new \DateTime();
		$this->getEntityMetadata()->setModified($modified);
	}

	/**
	 * @return bool
	 */
	public function isChanged(): bool
	{
		return $this->getEntityMetadata()->getState()->isNew()
			|| ($this->getEntityMetadata()->getOriginalData() !== $this->getData());
	}

	/**
	 * @return array
	 */
	public function getChangedData(): array
	{
		return Arrays::diff(
			$this->getData(),
			$this->getEntityMetadata()->getOriginalData()
		);
	}

	public function updateOriginalData()
	{
		$this->getEntityMetadata()->setOriginalData($this->getData());
	}

	/**
	 * @return array
	 */
	private function getData(): array
	{
		//add modified to data, so we can handle modified attribute changes
		return array_merge($this->toArray(), ['modified' => $this->getModified()]);
	}

	/**
	 * @return array
	 */
	protected function parentToArray(): array
	{
		return [
			'id' => $this->id,
			'created' => $this->getCreated(),
		];
	}

	/**
	 * @param string $relationSlug
	 * @return bool
	 */
	protected function relationIsNotSet(string $relationSlug): bool
	{
		if (!isset($this->setRelations[$relationSlug])) {
			$this->setRelations[$relationSlug] = true;
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public static function getShortName()
	{
		return Nette\Utils\Strings::after(get_called_class(), '\\', -1);
	}

	/**
	 * @return bool
	 */
	public function shouldPersist(): bool
	{
		return !$this->hasFlag(IEntity::FLAG_REMOVE);
	}

	/**
	 * @return bool
	 */
	public function shouldRemove(): bool
	{
		return $this->hasFlag(IEntity::FLAG_REMOVE);
	}
}