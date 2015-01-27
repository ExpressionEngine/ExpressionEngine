<?php

namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class FieldDataGateway extends Gateway {

	public function getFieldList()
	{
		$db = clone ee()->db;
		$db->_reset_select();
		$db->select(static::$_field_id_name);
		$db->from(static::$_field_table);

		$results = $db->get()->result_array();
		$fields = parent::getFieldList();

		foreach($results as $result_row)
		{
			$fields[] = 'field_id_' . $result_row[static::$_field_id_name];
			$fields[] = 'field_ft_' . $result_row[static::$_field_id_name];
		}

		return $fields;
	}

}


class FieldData {

	public $field_id;
	public $data;
	public $format;

	public function __construct($id)
	{
		$this->field_id = $id;
	}
}
