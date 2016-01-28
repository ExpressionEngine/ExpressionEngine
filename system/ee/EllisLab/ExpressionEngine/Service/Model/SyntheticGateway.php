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
 * ExpressionEngine Synthetic Gateway
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class SyntheticGateway extends Gateway {

	protected $table_name;
	protected $model;
	protected $fields;
	protected $values = array();

	public function __construct($table_name, $model)
	{
		$this->table_name = $table_name;
		$this->model = $model;
		$this->fields = $model::getClassFields();

		foreach ($this->fields as $field)
		{
			$this->values[$field] = NULL;
		}
	}

	public function getTableName()
	{
		return $this->table_name;
	}

	public function getFieldList($cached = TRUE)
	{
		return $this->fields;
	}

	public function hasProperty($name)
	{
		return in_array($name, $this->fields);
	}

	public function setProperty($name, $value)
	{
		$this->values[$name] = $value;
		return $value;
	}

	public function getPrimaryKey()
	{
		$class = $this->model;
		return $class::getMetaData('primary_key');
	}

	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->values[$pk];
	}

	public function getValues()
	{
		return array_filter($this->values, function($value) {
			return isset($value);
		});
	}

}

// EOF
