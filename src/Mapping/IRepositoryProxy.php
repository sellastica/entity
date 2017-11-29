<?php
namespace Sellastica\Entity\Mapping;

interface IRepositoryProxy
{
	/**
	 * @return IRepository
	 */
	function getRepository();
}
