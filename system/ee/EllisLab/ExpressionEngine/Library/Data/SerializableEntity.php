<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Data;

use Serializable;

/**
 * This is basically like Entity, it's here to keep custom column Types
 * backwards compatible. Don't rely on this to stay, totally internal.
 */
class SerializableEntity extends Entity implements Serializable {

	/**
	 * Serialize
	 *
	 * @return String Serialized object
	 */
	public function serialize()
	{
		return serialize($this->getSerializeData());
	}

	/**
	 * Unserialize
	 *
	 * @param String $serialized Serialized object
	 * @return void
	 */
	public function unserialize($serialized)
	{
		$this->__construct();
		$this->setSerializeData(unserialize($serialized));
	}

	/**
	 * Overridable getter for serialization
	 *
	 * @return Mixed Data to serialize
	 */
	protected function getSerializeData()
	{
		return $this->getRawValues();
	}

	/**
	 * Overridable setter for unserialization
	 *
	 * @param Mixed $data Data returned from `getSerializedData`
	 * @return void
	 */
	protected function setSerializeData($data)
	{
		// set() instead of fill() so properties are not lost on write
		$this->set($data);

		// restore new/existing primary key
		$this->setId($this->getId());

		// mark as clean, or all the backups will have null values
		$this->markAsClean();
	}
}
