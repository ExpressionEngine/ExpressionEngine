<?php

namespace EllisLab\ExpressionEngine\Service\Model;

class Gateway {

	protected $_dirty = array();

	public static function getMetaData($item)
	{
		if ($item == 'field_list')
		{
			$vars = get_class_vars(get_called_class());
			$fields = array();

			foreach ($vars as $key => $value)
			{
				if ($key[0] != '_')
				{
					$fields[] = $key;
				}
			}

			return $fields;
		}

		$item = '_'.$item;
		return static::$$item;
	}

	public function __set($key, $value)
	{
		if ($this->hasProperty($key))
		{
			$this->setProperty($key, $value);
		}
	}

	/**
	 *
	 */
	public function hasProperty($name)
	{
		return (property_exists($this, $name) && $name[0] !== '_');
	}

	public function setProperty($name, $value)
	{
		$this->$name = $value;
		return $value;
	}

	public function getTable()
	{
		return static::$_table_name;
	}

	public function getPrimaryKey()
	{
		return static::$_primary_key;
	}

	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->$pk;
	}

	public function fill($values)
	{
		foreach ($values as $key => $value)
		{
			if ($this->hasProperty($key))
			{
				$this->setProperty($key, $value);
			}
		}
	}

	public function getValues()
	{
		$values = get_object_vars($this);

		foreach ($values as $key => $value)
		{
			if ( ! isset($value))
			{
				unset($values[$key]);
			}

			if ($key[0] == '_')
			{
				unset($values[$key]);
			}
		}

		return $values;
	}
}