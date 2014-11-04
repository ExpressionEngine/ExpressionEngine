<?php namespace EllisLab\ExpressionEngine\Service\Model\Query;

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
 * ExpressionEngine MetaCache
 *
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class MetaCache {

	public $model_name;
	public $model_class;

	private $pk;
	private $tables = NULL;
	private $gateways = array();
	private $gateway_meta = array();

	public function __construct($alias_service, $model_name)
	{
		$model_class = $alias_service->getRegisteredClass($model_name);

		$this->model_name = $model_name;
		$this->model_class = $model_class;

		$this->pk = $model_class::getMetaData('primary_key');
		$gateways = $model_class::getMetaData('gateway_names');

		foreach ($gateways as $name)
		{
			$class = $alias_service->getRegisteredClass($name);

			$this->gateways[$name] = $class;
		}
	}

	public function getPrimaryKey()
	{
		return $this->pk;
	}

	public function getName()
	{
		return $this->model_name;
	}

	public function getClass()
	{
		return $this->model_class;
	}

	public function getPrimaryTable()
	{
		return current($this->getTables());
	}

	public function getTables()
	{
		return array_keys($this->getFields());
	}

	public function findTable($field)
	{
		foreach ($this->getFields() as $table => $fields)
		{
			if (in_array($field, $fields))
			{
				return $table;
			}
		}

		return NULL;
	}

	public function getFields()
	{
		if ( ! isset($this->tables))
		{
			$table_fields = array();

			foreach ($this->gateways as $name => $class)
			{
				$table_fields[$class::getMetaData('table_name')] = $class::getMetaData('field_list');
			}

			$this->tables = $table_fields;
		}

		return $this->tables;
	}
}
