<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

class CompositeColumn implements Mixin {

	protected $scope;
	protected $columns;
	protected $objects;

	/**
	 *
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