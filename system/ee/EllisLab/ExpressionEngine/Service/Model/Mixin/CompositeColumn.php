<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Composite Column Mixin
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CompositeColumn implements Mixin {

	/**
	 * @var Parent scope
	 */
	protected $scope;

	/**
	 * @var List of column names with their class and property names
	 */
	protected $columns;

	/**
	 * @var Array of column instances
	 */
	protected $objects = array();

	/**
	 * @param Object $scope Parent object
	 */
	public function __construct($scope)
	{
		$this->scope = $scope;
		$this->columns = $scope->getCompositeColumns();
	}

	/**
	 * Get the mixin name
	 *
	 * @return String mixin name
	 */
	public function getName()
	{
		return 'Model:CompositeColumn';
	}

	/**
	 * Helper for __call to extract the column name from the
	 * get<ColumnName> method.
	 *
	 * @param String $method Called method
	 * @return String column name, if it exists
	 */
	public function getCompositeColumnNameFromMethod($method)
	{
		if (substr($method, 0, 3) == 'get')
		{
			$name = substr($method, 3);

			if ($this->hasCompositeColumn($name))
			{
				return $name;
			}
		}

		return NULL;
	}

	/**
	 * Check if a column of a given name exists
	 *
	 * @param String $name Column name
	 * @return bool Has column of $name?
	 */
	public function hasCompositeColumn($name)
	{
		return array_key_exists($name, $this->columns);
	}

	/**
	 * Get a column by name
	 *
	 * @param String $name Column name
	 * @return Column object
	 */
	public function getCompositeColumn($name)
	{
		if ( ! isset($this->objects[$name]))
		{
			$this->objects[$name] = $this->newColumn($name);
		}

		return $this->objects[$name];
	}

	/**
	 * Save composite columns
	 */
	public function saveCompositeColumns()
	{
		foreach ($this->objects as $name => $object)
		{
			$value = $object->getValue();
			$property = $this->columns[$name]['property'];

			$this->scope->setProperty($property, $value);
		}

		return $this->scope;
	}

	/**
	 * Helper to create the column object
	 *
	 * @param String $name column name
	 * @return Column object
	 */
	protected function newColumn($name)
	{
		$definition = $this->columns[$name];

		$class = $definition['class'];
		$property = $definition['property'];

		$obj = new $class();
		$obj->fill($this->scope->$property);

		return $obj;
	}
}