<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity;

class FieldDataEntity extends Entity {
	protected $fields = array();

	public function __get($name)
	{
		if (strpos($name, 'field_id_') === 0)
		{
			list($id) = sscanf($name, 'field_id_%d');
			return $this->fields[$id]->data;
		}
		elseif(strpos($name, 'field_fmt_') === 0)
		{
			list($id) = sscanf($name, 'field_fmt_%d');
			return $this->fields[$id]->format;
		}
	}

	public function __set($name, $value)
	{
		if (strpos($name, 'field_id_') === 0)
		{
			list($id) = sscanf($name, 'field_id_%d');
			if ( ! isset($this->fields[$id]))
			{
				$this->fields[$id] = new FieldData($id);
			}
			$this->fields[$id]->data = $value;
			$this->dirty[$name] = TRUE;
		}
		elseif(strpos($name, 'field_fmt_') === 0)
		{
			list($id) = sscanf($name, 'field_fmt_%d');
			if ( ! isset($this->fields[$id]))
			{
				$this->fields[$id] = new FieldData($id);
			}
			$this->fields[$id]->format = $value;
			$this->dirty[$name] = TRUE;
		}
		
	}


	public static function getMetaData($key)
	{
		if ($key === 'field_list')
		{
			if ( ! isset(static::$meta['number_of_fields']))
			{
				$db = clone ee()->db;
				static::$meta['number_of_fields'] = $db->count_all(static::getMetaData('field_table'));
			}

			$field_names = parent::getMetaData('field_list');		
			for($id = 1; $id <= static::$meta['number_of_fields']; $id++)
			{
				$field_names['field_id_' . $id] = NULL;
				$field_names['field_ft_' . $id] = NULL;
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
