<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

use EllisLab\ExpressionEngine\Core\AliasService;
use EllisLab\ExpressionEngine\Model\Model;


class RelationshipData {

	public $name;
	public $model;
	public $type;
	public $key;
	public $to_key;
	public $is_parent;

	private $from;
	private $alias_service;

	public function __construct(AliasService $alias_service, $from_class, $name)
	{
		$this->from = $from_class;
		$this->alias_service = $alias_service;

		$relationships = $from_class::getMetaData('relationships');

		$keys = array(
			'name'		=> $name,
			'model'		=> $name,
			'type'		=> NULL,
			'key'		=> NULL,
			'to_key'	=> NULL,
			'is_parent'	=> FALSE
		);

		// make sure all the keys are there - as null if not given
		$data = $relationships[$name];
		$data = array_merge($keys, $data);

		foreach ($data as $key => $value)
		{
			// prevent "clever" overriding of our private variables
			if (array_key_exists($key, $keys))
			{
				$this->$key = $value;
			}
		}

		$this->normalizeKeys();
	}

	// TODO should not need to pass this in, it's from_class
	public function getInverseOn($model)
	{
		$all = $model->getGraphNode()->getAllEdges();

		foreach ($all as $name => $info)
		{
			// TODO invert other info
			if ($info->key == $this->to_key &&
				$info->to_key == $this->key)
			{
				return $info;
			}
		}

		return NULL;
	}

	public function connect($from_instance, $to_model_or_collection)
	{
		switch ($this->type)
		{
			case 'one_to_one':
				if ($this->key != $from_instance::getMetaData('primary_key'))
				{
					$from_instance->{$this->key} = $to_model_or_collection->{$this->to_key};
				}

				$to_class = $this->to_class;

				if ($this->to_key != $to_class::getMetaData('primary_key'))
				{
					$to_model_or_collection->{$this->to_key} = $from_instance->{$this->key};
				}
				break;
			case 'one_to_many':
				foreach ($to_model_or_collection as $model)
				{
					$model->{$this->to_key} = $from_instance->{$this->key};
				}
				break;
			case 'many_to_one':
				$from_instance->{$this->key} = $to_model_or_collection->{$this->to_key};
				break;
			case 'many_to_many':
				// nada
				break;
		}
	}

	/**
	 * Need to do a whole bunch of work to figure out which key goes where.
	 */
	private function normalizeKeys()
	{
		$from = $this->from;
		$to_class = $this->alias_service->getRegisteredClass($this->model);

		// use a reasonable key structure
		switch ($this->type)
		{
			case 'one_to_many':

				// default: primary key of the one side (e.g group_id for template groups and templates)
				$this->key	  = $this->key ?: $from::getMetaData('primary_key');
				$this->to_key = $this->to_key ?: $this->key;
				$this->is_parent = TRUE;
				break;

			case 'many_to_one':

				// default: same as one_to_many, but looked up in the other direction
				$this->to_key = $this->to_key ?: $to_class::getMetaData('primary_key');
				$this->key	  = $this->key ?: $this->to_key;
				$this->is_parent = FALSE;
				break;

			case 'many_to_many':

				// default: both primary keys on pivot
				$this->key	  = $this->key ?: $from::getMetaData('primary_key');
				$this->to_key = $this->to_key ?: $to_class::getMetaData('primary_key');
				$this->is_parent = FALSE;
				break;

			case 'one_to_one':

				// default: opposite primary key in list or declared
				if ( ! $this->key && ! $this->to_key)
				{
					if (property_exists($to_class, $from::getMetaData('primary_key')))
					{
						$this->key	  = $from::getMetaData('primary_key');
						$this->to_key = $this->key;
						$this->is_parent = TRUE;
					}
					else if (property_exists($from, $to_class::getMetaData('primary_key')))
					{
						$this->key	  = $to_class::getMetaData('primary_key');
						$this->to_key = $this->key;
						$this->is_parent = FALSE;
					}
				}
				else
				{
					$this->key	  = $this->key ?: $to_class::getMetaData('primary_key');
					$this->to_key = $this->to_key ?: $from::getMetaData('primary_key');
					$this->is_parent = ($this->to_key == $from::getMetaData('primary_key'));
				}
				break;
			default:
				throw new \Exception('Relationship type not specified.');
		}

		// useful to know
		$this->to_class = $to_class;
		$this->is_collection = (substr($this->type, -4) == 'many');
	}
}