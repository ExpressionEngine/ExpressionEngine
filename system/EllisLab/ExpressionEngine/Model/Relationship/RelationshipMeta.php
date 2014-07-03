<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

use EllisLab\ExpressionEngine\Core\AliasService;

class RelationshipMeta {

	const METHOD_JOIN = 'join';
	const METHOD_SUBQUERY = 'subquery';

	const TYPE_ONE_TO_ONE = 'one_to_one';
	const TYPE_ONE_TO_MANY = 'one_to_many';
	const TYPE_MANY_TO_ONE = 'many_to_one';
	const TYPE_MANY_TO_MANY = 'many_to_many';

	protected $alias_service;

	protected $type = NULL;
	protected $method = self::METHOD_JOIN;
	protected $relationship_name = NULL;
	protected $relationship_alias = NULL;

	protected $from_model_name = NULL;
	protected $from_model_class = NULL;
	protected $from_table = NULL;
	protected $from_table_alias = NULL;

	protected $to_model_name = NULL;
	protected $to_model_class = NULL;
	protected $to_table = NULL;
	protected $to_table_alias = NULL;
	protected $join_key = NULL;
	protected $joined_tables = array();

	protected $from_key = NULL;
	protected $to_key = NULL;

	protected $pivot_table = NULL;
	protected $pivot_from_key = NULL;
	protected $pivot_to_key = NULL;

	public function __construct(AliasService $alias_service, $type, $relationship_name, array $from, array $to)
	{
		$this->alias_service = $alias_service;

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
		$from_gateway_name = $from_key_map[$this->from_key];
		$from_gateway_class = $this->alias_service->getRegisteredClass($from_gateway_name);
		$this->from_table = $from_gateway_class::getMetaData('table_name');

		// Poplate to_table
		$gateway_relationships = $from_gateway_class::getMetaData('related_gateways');
		$gateway_relationship = $gateway_relationships[$this->from_key];
		if ( ! isset ($gateway_relationship['gateway']))
		{
			$gateway_relationship = $gateway_relationship[$this->relationship_name];
		}

		$to_gateway_name = $gateway_relationship['gateway'];
		$to_gateway_class = $this->alias_service->getRegisteredClass($to_gateway_name);
		$this->to_table = $to_gateway_class::getMetaData('table_name');

		// Assuming we're joining tables in the same model across the main
		// gateway's primary key.
		$this->join_key = $to_gateway_class::getMetaData('primary_key');

		$to_model_class = $this->to_model_class;
		$joined_gateway_names = $to_model_class::getMetaData('gateway_names');

		$key = array_search($to_gateway_name, $joined_gateway_names);
		unset($joined_gateway_names[$key]);

		foreach ($joined_gateway_names as $joined_gateway_name)
		{
			$joined_gateway_class = $this->alias_service->getRegisteredClass($joined_gateway_name);
			$this->joined_tables[$joined_gateway_class::getMetaData('primary_key')] = $joined_gateway_class::getMetaData('table_name');
		}

		if ($this->to_key !== $gateway_relationship['key'])
		{
			throw new \Exception('Foreign keys in relationship are not equal.  In "' . $this->relationship_name . '" from "' . $this->from_model_name
				. '" to "' . $this->to_model_name . '", to_key "' . $this->to_key . '" does not equal the key in the Gateway "' . $from_gateway_name . '", "' . $gateway_relationship['key'] . '"');
		}

		// Populate pivots
		if ($this->type === self::TYPE_MANY_TO_MANY)
		{
			$this->pivot_table = $gateway_relationship['pivot_table'];
			$this->pivot_from_key = $gateway_relationship['pivot_key'];
			$this->pivot_to_key = $gateway_relationship['pivot_foreign_key'];
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
