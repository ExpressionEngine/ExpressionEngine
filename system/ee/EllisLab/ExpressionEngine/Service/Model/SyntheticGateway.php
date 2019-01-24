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
