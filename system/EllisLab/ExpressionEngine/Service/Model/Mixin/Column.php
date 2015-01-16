<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;

class Column implements Mixin {

	protected $scope;
	protected $columns;
	protected $objects;
	protected $manager;

	public function __construct($scope)
	{
		$this->scope = $scope;
		$this->columns = $scope->getColumnDefinitions();
	}

	/**
	 * Satisfy the interface and allow for forwarding to our
	 * column entities.
	 */
	public function setMixinManager($manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Intercept calls to getColumName()
	 */
	public function __call($fn, $args)
	{
		if (substr($fn, 0, 3) == 'get')
		{
			$column = substr($fn, 3);

			if ($this->hasColumn($column))
			{
				return $this->getColumn($column);
			}
		}

		return NULL;
	}

	/**
	 * Add hasColumn
	 */
	public function hasColumn($name)
	{
		return array_key_exists($name, $this->columns);
	}

	/**
	 * Add getColumn
	 */
	public function getColumn($name)
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
	public function saveColumns()
	{
		foreach ($this->objects as $name => $object)
		{
			$value = $object->getValue();
			$property = $this->columns[$name]['property'];

			$this->scope->setProperty($property, $value);
		}
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