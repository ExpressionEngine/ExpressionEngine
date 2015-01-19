<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

class CompositeColumn implements Mixin {

	protected $scope;
	protected $manager;
	protected $columns;
	protected $objects;

	public function __construct($scope, $manager)
	{
		$this->scope = $scope;
		$this->manager = $manager;
		$this->columns = $scope->getCompositeColumns();
	}

	/**
	 * Get the mixin name
	 */
	public function getName()
	{
		return 'Model:CompositeColumn';
	}

	/**
	 *
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

		return FALSE;
	}

	/**
	 * Add hasCompositeColumn
	 */
	public function hasCompositeColumn($name)
	{
		return array_key_exists($name, $this->columns);
	}

	/**
	 * Add getCompositeColumn
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
	 * Save columns
	 *
	 * TODO do this onBeforeSave/onBeforeValidate
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
	 */
	protected function newColumn($name)
	{
		$definition = $this->columns[$name];

		$class = $definition['class'];
		$property = $definition['property'];

		$obj = new $class();
		$obj->fill($this->scope->$property);


		$this->manager->forward($obj);

		return $obj;
	}
}