<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
