<?php
namespace Sellastica\Entity\Relation;

class RelationGetManager extends RelationManager
{
	/** @var string Database column, we want to get, if we search for one column only */
	private $resultEntityId;
	/** @var string Defines database column, which is used for a key, if we want to get an associative array */
	private $relationKey;


	/**
	 * Database column, we want to get, if we search ofr one column only
	 * @return string
	 */
	public function getResultEntityId()
	{
		return $this->resultEntityId;
	}

	/**
	 * @param string $resultEntityId
	 */
	public function setResultEntityId($resultEntityId)
	{
		$this->resultEntityId = $resultEntityId;
	}

	/**
	 * Defines database column, which is used for a key, if we want to get an associative array
	 * @return string
	 */
	public function getRelationKey()
	{
		return $this->relationKey;
	}

	/**
	 * @param string $relationKey
	 */
	public function setRelationKey($relationKey)
	{
		$this->relationKey = $relationKey;
	}
}