<?php

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Gateway
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
