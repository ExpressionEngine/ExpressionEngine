<?php
namespace EllisLab\ExpressionEngine\Service\Model;

class MetaDataReader {

	protected $name;
	protected $class;

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
		return $class::getMetaData('validation_rules');
	}

	/**
	 *
	 */
	public function getRelationships()
	{
		$class = $this->class;
		return $class::getMetaData('relationships');
	}

	/**
	 *
	 */
	public function getGateways()
	{
		$class = $this->class;
		$gateway_names = $class::getMetaData('gateway_names');

		if ( ! count($gateway_names))
		{
			// todo synthesize from model
		}

		$gateways = array();

		$prefix = $this->getNamespacePrefix();

		foreach ($gateway_names as $name)
		{
			$gateways[$name] = $prefix.'\\Gateway\\'.$name;
		}

		return $gateways;
	}

	/**
	 *
	 */
	public function getTables()
	{
		$tables = array();

		$gateways = $this->getGateways();

		foreach ($gateways as $name => $class)
		{
			$table = $class::getMetaData('table_name');
			$fields = $class::getMetaData('field_list');

			$tables[$table] = $fields;
		}

		return $tables;
	}

	/**
	 *
	 */
	public function getTableNamesByGateway()
	{
		$gateways = $this->getGateways();

		$table_names = array();

		foreach ($gateways as $name => $class)
		{
			$table_names[$name] = $class::getMetaData('table_name');
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
