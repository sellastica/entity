<?php
namespace Sellastica\Entity;

use Nette\DI\Container;

/**
 * Simple proxy to transactions
 */
class Transaction
{
	/** @var \Dibi\Connection */
	private $connection;


	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->connection = $container->getService('dibi');
	}

	public function begin()
	{
		$this->connection->begin();
	}

	public function commit()
	{
		$this->connection->commit();
	}

	public function rollback()
	{
		$this->connection->rollback();
	}
}