<?php
namespace Sellastica\Entity\Exception;

/**
 * This exception is thrown by duplicate entry to the storage
 * e.g. primary or unique key
 */
class StorageDuplicateEntryException extends StorageException
{
}