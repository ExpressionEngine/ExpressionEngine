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
 * Model Service Meta Data Reader
 */
class MetaDataReader {

	protected $name;
	protected $class;
	protected $tables;

	public function __construct($name, $class)
	{
		$this->name = $name;
		$this->class = trim($class, '\\');
	}

	/**
	 *
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 */
	public function getClass()
	{
		return $this->class;
	}

	/**
	 *
	 */
	public function getPrimaryKey()
	{
		$class = $this->class;
		return $class::getMetaData('primary_key');
	}

	/**
	 *
	 */
	public function getValidationRules()
	{
		$class = $this->class;
		$validation = $class::getMetaData('validation_rules');

		return $validation ?: array();
	}

	/**
	 *
	 */
	public function getRelationships()
	{
		$class = $this->class;
		$relationships = $class::getMetaData('relationships');

		return $relationships ?: array();
	}

	/**
	 *
	 */
	public function getEvents()
	{
		$class = $this->class;
		$relationships = $class::getMetaData('events');

		return $relationships ?: array();
	}

	/**
	 * Get binary_comparisons array
	 */
	public function getBinaryComparisons()
	{
		$class = $this->class;
		$binary_comparisons = $class::getMetaData('binary_comparisons');

		return $binary_comparisons ?: array();
	}

	/**
	 * @return bool
	 */
	public function publishesHooks()
	{
		$class = $this->class;
		$name = $class::getMetaData('hook_id');

		return ($name != '');
	}

	/**
	 *
	 */
	public function getGateways()
	{
		$class = $this->class;
		$gateway_names = $class::getMetaData('gateway_names');

		if ( ! isset($gateway_names))
		{
			$table_name = $class::getMetaData('table_name');

			if ( ! $table_name)
			{
				throw new \Exception("Model '{$class}' did not declare a table.");
			}

			return array($table_name => $this->synthesize($class, $table_name));
		}

		$gateways = array();

		$prefix = $this->getNamespacePrefix();

		foreach ($gateway_names as $name)
		{
			$gateway_class = $prefix.'\\Gateway\\'.$name;
			$gateways[$name] = new $gateway_class;
		}

		return $gateways;
	}

	/**
	 *
	 */
	protected function synthesize($model, $table_name)
	{
		return new SyntheticGateway($table_name, $model);
	}

	/**
	 * @return array [TableName => [columns]]
	 */
	public function getTables($cached = TRUE)
	{
		if (isset($this->tables) && $cached)
		{
			return $this->tables;
		}

		$tables = array();

		$gateways = $this->getGateways();

		foreach ($gateways as $name => $object)
		{
			$table = $object->getTableName();
			$fields = $object->getFieldList($cached);

			$tables[$table] = $fields;
		}

		$this->tables = $tables;
		return $tables;
	}

	/**
	 * Get a table for a given column
	 */
	public function getTableForField($field)
	{
		$class = $this->class;
		$table = $class::getMetaData('table_name');

		if ($table)
		{
			return $table;
		}

		foreach ($this->getTables() as $table => $fields)
		{
			if (in_array($field, $fields))
			{
				return $table;
			}
		}
	}

	/**
	 *
	 */
	protected function getNamespacePrefix()
	{
		return substr($this->class, 0, strrpos($this->class, '\\'));
	}
}

// EOF
