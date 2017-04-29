<?php

namespace EllisLab\ExpressionEngine\Service\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
