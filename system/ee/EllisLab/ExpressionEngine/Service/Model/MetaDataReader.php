<?php

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Meta Data Reader
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
	 *
	 */
	public function getTables()
	{
		if (isset($this->tables))
		{
			return $this->tables;
		}

		$tables = array();

		$gateways = $this->getGateways();

		foreach ($gateways as $name => $object)
		{
			$table = $object->getTableName();
			$fields = $object->getFieldList();

			$tables[$table] = $fields;
		}

		$this->tables = $tables;
		return $tables;
	}

	/**
	 *
	 */
	public function getTableNamesByGateway()
	{
		$gateways = $this->getGateways();

		$table_names = array();

		foreach ($gateways as $name => $object)
		{
			$table_names[$name] = $object->getTableName();
		}

		return $table_names;
	}

	/**
	 *
	 */
	public function getTableForField($field)
	{
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
