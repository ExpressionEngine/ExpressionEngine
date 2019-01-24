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
 * Model Service Variable Column Model
 */
abstract class VariableColumnModel extends Model {

	/**
	 * @var Array Dictionary of values that have been set that were not explicitly
	 *            defined in the subclass.
	 */
	protected $_variable_values = array();

	/**
	 * Fill a value without passing through a getter
	 *
	 * We override this to bypass an internal __get call on unknown properties.
	 *
	 * @param String $k Key to fill
	 * @param Mixed  $v Value of fill
	 * @return $this
	 */
	public function fillProperty($k, $v)
	{
		if (parent::hasProperty($k))
		{
			return parent::fillProperty($k, $v);
		}

		if ($this->hasProperty($k))
		{
			$v = $this->filter('fill', $v, $k);
			$this->_variable_values[$k] = $v;
		}

		return $this;
	}

	/**
	 * Check if a property exists. By default, variable name properties are any
	 * that don't start with an underscore.
	 *
	 * @param String $name Property name
	 * @return bool Class has property?
	 */
	public function hasProperty($name)
	{
		return ($name !== '' && $name[0] != '_');
	}

	/**
	 * Get property without passing through a getter
	 *
	 * @param String $name Property name
	 * @return Mixed Property value
	 */
	public function getRawProperty($name)
	{
		if (parent::hasProperty($name))
		{
			return parent::getRawProperty($name);
		}

		return $this->getVariableValue($name);
	}

	/**
	 * Set property without passing through a setter
	 *
	 * @param String $name Property name
	 * @param Mixed $value Property value
	 * @return $this
	 */
	public function setRawProperty($name, $value)
	{
		if (parent::hasProperty($name))
		{
			return parent::setRawProperty($name, $value);
		}

		$this->backupIfChanging($name, $this->getVariableValue($name), $value);

		$this->_variable_values[$name] = $value;

		return $this;
	}

	/**
	 * Get values as an array
	 *
	 * @return array of values
	 */
	public function getValues()
	{
		return array_merge(parent::getValues(), $this->_variable_values);
	}

	/**
	 * Helper method to get the value of an undeclared column.
	 *
	 * @return String $name Name of the column
	 * @return Mixed Value of the column
	 */
	protected function getVariableValue($name)
	{
		if ( ! array_key_exists($name, $this->_variable_values))
		{
			return NULL;
		}

		return $this->_variable_values[$name];
	}
}

// EOF
