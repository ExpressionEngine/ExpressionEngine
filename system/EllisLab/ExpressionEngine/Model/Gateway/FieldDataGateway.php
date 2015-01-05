<?php

namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class FieldDataGateway extends Gateway {

	protected $_fields = array();

	public function __get($name)
	{
		if (strpos($name, 'field_id_') === 0)
		{
			list($id) = sscanf($name, 'field_id_%d');
			return $this->_fields[$id]->data;
		}
		elseif (strpos($name, 'field_fmt_') === 0)
		{
			list($id) = sscanf($name, 'field_fmt_%d');
			return $this->_fields[$id]->format;
		}
	}

	public function __set($name, $value)
	{
		if (strpos($name, 'field_id_') === 0)
		{
			list($id) = sscanf($name, 'field_id_%d');
			if ( ! isset($this->_fields[$id]))
			{
				$this->_fields[$id] = new FieldData($id);
			}
			$this->_fields[$id]->data = $value;
			$this->dirty[$name] = TRUE;
		}
		elseif (strpos($name, 'field_fmt_') === 0)
		{
			list($id) = sscanf($name, 'field_fmt_%d');
			if ( ! isset($this->_fields[$id]))
			{
				$this->_fields[$id] = new FieldData($id);
			}
			$this->_fields[$id]->format = $value;
			$this->dirty[$name] = TRUE;
		}

	}


	public static function getMetaData($key)
	{
		if ($key === 'field_list')
		{
			$db = clone ee()->db;
			$db->_reset_select();
			$db->select(static::getMetaData('field_id_name'));
			$db->from(static::getMetaData('field_table'));

			$results = $db->get()->result_array();
			$field_names = parent::getMetaData('field_list');

			foreach($results as $result_row)
			{
				$field_names['field_id_' . $result_row[static::getMetaData('field_id_name')]] = NULL;
				$field_names['field_ft_' . $result_row[static::getMetaData('field_id_name')]] = NULL;
			}

			return $field_names;
		}

		return parent::getMetaData($key);

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
