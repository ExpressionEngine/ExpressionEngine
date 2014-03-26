<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

/**
 * Relationship fluent interface.
 */
class RelationshipFluentBuilder {

	private $data;
	private $is_sealed = FALSE;

	// dependencies
	private $bag;
	private $builder;
	private $alias_service;

	public function __construct($from_object, $data)
	{
		$this->data = $data;
		$this->data->from_object = $from_object;
	}

	public function to($model_name, $to_key)
	{
		$this->allowOnce('to_model', 'relationship target (`to()`).');

		$this->data->to_model = $to_model;
		$this->data->to_key = $to_key;

		return $this;
	}

	public function on($from_key)
	{
		$this->allowOnce('from_key', 'relationship key (`on()`).');

		$this->data->from_key = $from_key;

		return $this;
	}

	public function useAs($alias)
	{
		$this->allowOnce('alias', 'relationship alias (`as()`).');

		$this->data->alias = $alias;

		return $this;
	}

	public function type($type)
	{
		$this->allowOnce('type', 'relationship type (`type()`)');

		$this->data->type = $type;

		return $this;
	}

	public function relate()
	{
		// default the alias to the to_model name
		$this->data->alias = $this->data->alias ?: $this->to_model;
		$this->data->relate();
	}

	private function allowOnce($property, $human_description)
	{
		if (isset($this->data->$propery))
		{
			throw new \Exception('Cannot redreclare '.$human_description);
		}

		return TRUE;
	}
}