<?php
namespace EllisLab\ExpressionEngine\Model\Query;

class ModelRelationshipMeta {

	const METHOD_JOIN = 'join';
	const METHOD_SUBQUERY = 'subquery';

	const TYPE_ONE_TO_ONE = 'oneToOne';
	const TYPE_ONE_TO_MANY = 'oneToMany';
	const TYPE_MANY_TO_ONE = 'manyToOne';
	const TYPE_MANY_TO_MANY = 'manyToMany';

	protected $type = NULL;
	protected $method = self::METHOD_JOIN;
	protected $relationship_name = NULL;

	protected $from_model_name = NULL;
	protected $from_model = NULL;

	protected $to_model_name = NULL;

	protected $from_key = NULL;
	protected $to_key = NULL;

	protected $pivot_table = NULL;
	protected $pivot_from_key = NULL;
	protected $pivot_to_key = NULL;

	public function __construct($type)
	{
		$this->type = $type;
		$this->from_model = $from_model;
	}

	public function __set($name, $value)
	{
		if (property_exists($this, $name))
		{
			$this->{$name} = $value;
		}
		throw new Exception('Property does not exist!');
	}

	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->{$name};
		}
		throw new Exception('Property does not exist!');
	}

	public function override(array $meta)
	{
		if (isset($meta['method']))
		{
			$this->method = $meta['method'];
		}
	}
}
