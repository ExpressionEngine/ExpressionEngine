<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

class RelationshipData {

	public $type;
	public $from_object;
	public $to_model;
	public $to_key;
	public $alias;
	public $from_key;

	private $bag;
	private $alias_service;
	private $builder;

	public function __construct($bag, $alias_service, $builder)
	{
		$this->bag = $bag;
		$this->alias_service = $alias_service;
		$this->builder = $builder;
	}

	/**
	 * Return the relationship
	 */
	public function relate()
	{
		// if we already have data, we return it. TODO shortcut this
		if ($this->bag->has($this->alias))
		{
			$related = $this->bag->get($this->alias);

			if ($this->relates_to_many())
			{
				return $related_collection;
			}

			return $related_collection->first();
		}

		// No data, check if we're in a query
		if ($this->from_object->getId() === NULL)
		{
			return new RelationshipMeta(
				$this->alias_service,
				$this->type,
				$this->alias,
				$this->collect_to_data(),
				$this->collect_from_data()
			);
		}

		// Lazy Load
		// If we haven't hit one of the previous cases, then this
		// is a lazy load on an existing model.
		$result = $this->run_lazy_query();

		// Store the result
		$this->bag->add($this->alias, $result);

		return $result;
	}

	private function relates_to_many()
	{
		switch ($this->type)
		{
			case 'one-to-many':
			case 'many-to-many':
				return TRUE;
			case 'many-to-one':
			case 'one-to-one':
				return FALSE;
			default:
				throw new \Exception('Unknown type!');
		}
	}

	private function collect_from_data()
	{
		$from_class = get_class($this->from_object);

		return array(
			'model_class' => $from_class,
			'model_name'  => substr($from_class, strrpos($from_class, '\\') + 1),
			'key'		  => $this->from_key
		);
	}

	private function collect_to_data()
	{
		return array(
			'model_class' => $this->alias_service->getRegisteredClass($this->to_model),
			'model_name'  => $this->to_model,
			'key'		  => $this->to_key
		);
	}

	private function run_lazy_query()
	{
		$to_identifier = $this->to_model.'.'.$this->to_key;
		$from_key_value = $this->from_object->{$this->from_key};

		$query = $this->builder->get($this->to_model);
		$query->filter($to_identifier, $from_key_value);

		if ($this->relates_to_many())
		{
			return $query->all();
		}

		return $query->first();
	}
}