<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * Model Service Synthetic Gateway
 */
class SyntheticGateway extends Gateway {

	protected $table_name;
	protected $model;
	protected $fields;

	public function __construct($table_name, $model)
	{
		$this->table_name = $table_name;
		$this->model = $model;
		$this->fields = $model::getClassFields();
	}

	public function getTableName()
	{
		return $this->table_name;
	}

	public function getFieldList($cached = TRUE)
	{
		return $this->fields;
	}

	public function getPrimaryKey()
	{
		$class = $this->model;
		return $class::getMetaData('primary_key');
	}
}

// EOF
