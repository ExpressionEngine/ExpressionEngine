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
	protected $from_model_class = NULL;
	protected $from_table = NULL;

	protected $to_model_name = NULL;
	protected $to_model_class = NULL;
	protected $to_table = NULL;
	protected $to_joined_tables = NULL;

	protected $from_key = NULL;
	protected $to_key = NULL;

	protected $pivot_table = NULL;
	protected $pivot_from_key = NULL;
	protected $pivot_to_key = NULL;

	public function __construct($type, $relationship_name, array $from, array $to)
	{
		$this->type = $type;
		$this->relationship_name = $relationship_name;

		$this->from_model_name = $from['model_name'];
		$this->from_model_class = $from['model_class'];
		$this->from_key = $from['key'];

		$this->to_model_name = $to['model_name'];
		$this->to_model_class = $to['model_class'];
		$this->to_key = $to['key'];

		$this->initialize();
	}

	protected function initialize()
	{
		// Populate from_table
		$from_model_class = $this->from_model_class;
		$from_key_map = $from_model_class::getMetaData('key_map');	
		$from_entity_name = $from_key_map[$this->from_key];
		$from_entity_class = QueryBuilder::getQualifiedClassName($from_entity_name);
		$this->from_table = $from_entity_class::getMetaData('table_name');

		// Poplate to_table
		$entity_relationships = $from_entity_class::getMetaData('related_entities');
		$entity_relationship = $entity_relationships[$this->from_key];
		if ( ! isset ($entity_relationship['entity']))
		{
			$entity_relationship = $entity_relationship[$this->relationship_name];
		}

		$to_entity_name = $entity_relationship['entity'];
		$to_entity_class = QueryBuilder::getQualifiedClassName($to_entity_name);
		$this->to_table = $to_entity_class::getMetaData('table_name');

	/*	$to_model_class = $this->to_model_class;
		$to_entity_names = $to_model_class::getMetaData('entity_names'); */
		

		if ($this->to_key !== $entity_relationship['key'])
		{
			throw new \Exception('Foreign keys in relationship are not equal!');
		}
		
		// Populate pivots	
		if ($this->type === self::TYPE_MANY_TO_MANY)
		{
			$this->pivot_table = $entity_relationship['pivot_table'];
			$this->pivot_from_key = $entity_relationship['pivot_key'];
			$this->pivot_to_key = $entity_relationship['pivot_foreign_key'];
		}
	}

	public function __set($name, $value)
	{
		if (property_exists($this, $name))
		{
			$this->{$name} = $value;
			return;
		}
		throw new \Exception('Property "' . $name . '" does not exist!');
	}

	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->{$name};
		}
		throw new \Exception('Property "' . $name . '" does not exist!');
	}

	public function override(array $meta)
	{
		if (isset($meta['method']))
		{
			$this->method = $meta['method'];
		}
	}

}
