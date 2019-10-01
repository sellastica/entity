<?php
namespace Sellastica\Entity;

use Nette\DateTime;
use Nette\Utils\Paginator;

class Configuration
{
	/** @var Paginator|null */
	private $paginator;
	/** @var Sorter|null */
	private $sorter;
	/** @var \DateTime|null */
	private $lastModified;
	/** @var bool */
	private $retrieveIds = false;
	/** @var array */
	private $options = [];
	/** @var int|null */
	private $offset;


	/**
	 * @return Paginator|null
	 */
	public function getPaginator(): ?Paginator
	{
		return $this->paginator;
	}

	/**
	 * @param Paginator|null $paginator
	 * @return $this
	 */
	public function setPaginator(?Paginator $paginator)
	{
		$this->paginator = $paginator;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getLimit(): ?int
	{
		return $this->paginator
			? $this->paginator->getItemsPerPage()
			: NULL;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return $this
	 */
	public function setLimit(int $limit, int $offset = 0)
	{
		$this->paginator = new Paginator();
		$this->paginator->setItemsPerPage($limit);
		$this->paginator->setPage($offset / $limit + 1);
		return $this;
	}

	/**
	 * By normal way, offset is counted from page number and items count per page
	 * If we need to set different offset, we need to force it via this method
	 * @param int $offset
	 */
	public function forceOffset(int $offset): void
	{
		$this->offset = $offset;
	}

	/**
	 * @return int|null
	 */
	public function getOffset(): ?int
	{
		return $this->offset;
	}

	/**
	 * @return Sorter|null
	 */
	public function getSorter(): ?Sorter
	{
		return $this->sorter;
	}

	/**
	 * @param string $column
	 * @param bool $isAscending
	 * @return $this
	 */
	public function addSorterRule(string $column, bool $isAscending = true)
	{
		if (is_null($this->sorter)) {
			$this->sorter = new Sorter();
		}

		$this->sorter->addRule($column, $isAscending);
		return $this;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getLastModified(): ?\DateTime
	{
		return $this->lastModified;
	}

	/**
	 * @param \DateTime|string|null $lastModified
	 * @param string $format
	 * @return $this
	 */
	public function setLastModified($lastModified = null, string $format = 'Y-m-d H:i:s')
	{
		if (is_string($lastModified)) {
			$lastModified = DateTime::createFromFormat($format, $lastModified);
		}

		$this->lastModified = $lastModified;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getRetrieveIds(): bool
	{
		return $this->retrieveIds;
	}

	/**
	 * @param bool $retrieveIds
	 * @return $this
	 */
	public function setRetrieveIds(bool $retrieveIds = true)
	{
		$this->retrieveIds = $retrieveIds;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * @param string $name
	 * @param $value
	 * @return $this
	 */
	public function setOption(string $name, $value): Configuration
	{
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function getOption(string $name)
	{
		return $this->options[$name] ?? null;
	}

	/**
	 * @return string
	 */
	public function getCacheKey(): string
	{
		$tag = [];
		if (null !== $this->paginator) {
			$tag[] = 'l_' . $this->paginator->getLength();
			$tag[] = 'o_' . $this->paginator->getOffset();
		}

		if (null !== $this->sorter) {
			foreach ($this->sorter->getRules() as $rule) {
				$tag[] = $rule->getColumn() . '_' . (int)$rule->isAscending();
			}
		}

		return implode('_', $tag);
	}

	/**
	 * @return Configuration
	 */
	public static function create(): Configuration
	{
		return new self();
	}

	/**
	 * @param string $column
	 * @param bool $isAscending
	 * @return Configuration
	 */
	public static function sortBy(string $column, bool $isAscending = true): self
	{
		return (new self())->addSorterRule($column, $isAscending);
	}

	/**
	 * @param \DateTime|string $lastModified
	 * @param string|null $format
	 * @return Configuration
	 */
	public static function modifiedSince($lastModified, string $format = 'Y-m-d H:i:s'): self
	{
		return (new self())->setLastModified($lastModified, $format);
	}
}
