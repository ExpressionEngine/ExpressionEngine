<?php
namespace EllisLab\ExpressionEngine\Service\Model;

class Gateway {

	/**
	 *
	 */
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
	public function getTableName()
	{
		return static::$_table_name;
	}

	/**
	 *
	 */
	public function getFieldList()
	{
		$vars = get_object_vars($this);
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

	/**
	 *
	 */
	public function hasProperty($name)
	{
		return (property_exists($this, $name) && $name[0] !== '_');
	}

	/**
	 *
	 */
	public function setProperty($name, $value)
	{
		$this->$name = $value;
		return $value;
	}

	/**
	 *
	 */
	public function getPrimaryKey()
	{
		return static::$_primary_key;
	}

	/**
	 *
	 */
	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->$pk;
	}

	/**
	 *
	 */
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

	/**
	 *
	 */
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