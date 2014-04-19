<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

use EllisLab\ExpressionEngine\Core\AliasService;
use EllisLab\ExpressionEngine\Model\Model;


abstract class AbstractRelationship {

	public $name;
	public $model;
	public $type;
	public $key;
	public $to_key;
	public $is_parent;

	protected $from;
	protected $alias_service;

	public function __construct($from_class, $to_class, $name)
	{
		$this->from = $from_class;
		$this->to_class = $to_class;
		$this->is_collection = (substr($this->type, -4) == 'many');

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

	public function getInverseOn($model)
	{
		$all = $model->getGraphNode()->getAllEdges();

		foreach ($all as $name => $info)
		{
			// TODO invert other info
			if ($info->key == $this->to_key &&
				$info->to_key == $this->key &&
				$info->type == $this->inverse)
			{
				return $info;
			}
		}

		return NULL;
	}

	abstract public function connect($from_instance, $to_model_or_collection);

	/**
	 * Need to do a whole bunch of work to figure out which key goes where.
	 */
	abstract protected function normalizeKeys();
}