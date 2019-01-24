<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Content;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\VariableColumnModel;
use EllisLab\ExpressionEngine\Model\Content\FieldModel;

/**
 * ExpressionEngine FieldData Model
 */
class FieldData extends VariableColumnModel {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'channel_data_field_';

	protected $id;
	protected $entry_id;

	public function forField(FieldModel $field)
	{
		$this->_table_name = $field->getDataTable();
		return $this;
	}
}

// EOF
