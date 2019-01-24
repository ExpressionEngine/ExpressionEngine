<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * Model Service Gateway
 */
class Gateway {

	protected $_field_list_cache;
	protected $_values = array();

	/**
	 *
	 */
	public function getTableName()
	{
		return static::$_table_name;
	}

	/**
	 *
	 */
	public function getFieldList($cached = TRUE)
	{
		if (isset($this->_field_list_cache))
		{
			return $this->_field_list_cache;
		}

		$vars = get_object_vars($this);
		$fields = array();

		foreach ($vars as $key => $value)
		{
			if ($key[0] != '_')
			{
				$fields[] = $key;
			}
		}

		return $this->_field_list_cache = $fields;
	}

	/**
	 *
	 */
	public function hasField($name)
	{
		return in_array($name, $this->getFieldList());
	}

	/**
	 *
	 */
	public function setField($name, $value)
	{
		$this->_values[$name] = $value;
	}

	/**
	 *
	 */
	public function getPrimaryKey()
	{
		return static::$_primary_key;
	}

	/**
	 *
	 */
	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->_values[$pk];
	}

	/**
	 *
	 */
	public function fill($values)
	{
		foreach ($values as $key => $value)
		{
			if ($this->hasField($key))
			{
				$this->setField($key, $value);
			}
		}
	}

	/**
	 *
	 */
	public function getValues()
	{
		return $this->_values;
	}
}

// EOF
