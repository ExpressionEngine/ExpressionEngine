<?php
namespace EllisLab\ExpressionEngine\Service\Model;

class SyntheticGateway extends Gateway {

	protected $table_name;
	protected $model;
	protected $fields;
	protected $values = array();

	public function __construct($table_name, $model)
	{
		$this->table_name = $table_name;
		$this->model = $model;
		$this->fields = $model::getFields();

		foreach ($this->fields as $field)
		{
			$this->values[$field] = NULL;
		}
	}

	public function getTableName()
	{
		return $this->table_name;
	}

	public function getFieldList()
	{
		return $this->fields;
	}

	public function hasProperty($name)
	{
		return in_array($name, $this->fields);
	}

	public function setProperty($name, $value)
	{
		$this->values[$name] = $value;
		return $value;
	}

	public function getPrimaryKey()
	{
		$class = $this->model;
		return $model::getMetaData('primary_key');
	}

	public function getId()
	{
		$pk = $this->getPrimaryKey();
		return $this->values[$pk];
	}

	public function getValues()
	{
		return array_filter($this->values);
	}

}