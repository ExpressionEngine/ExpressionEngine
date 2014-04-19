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

	/**
	 * @param String  $from_class	fully qualified classname of originating model
	 * @param String  $to_class		fully qualified classname of target model
	 * @param String  $name			name of the relationship on $from_class
	 */
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

	/**
	 * Reverse a relationship to a given model.
	 *
	 * @param Model		$model		 Model to reverse to. TODO accept class name?
	 * @return AbstractRelationship  Relationship going in the other direction
	 */
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

	/**
	 * Code needed to connect the ids between instances of two models of this
	 * relationship type.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	abstract public function connect($from_instance, $to_model_or_collection);

	/**
	 * Code to figure out which side is the parent and how the given keys
	 * connect the models.
	 *
	 * Typically this will need to figure out these three:
	 *
	 * $this->is_parent
	 * $this->key
	 * $this->to_key
	 *
	 * For example, in a oneToMany relationship, the current model is always
	 * the parent, the key is the current primary key, and the to_key is either
	 * is a column on the other model that is either user specified or matches
	 * the from_model's primary key.
	 *
	 * This allows us to do minimize how much the developer needs to specify.
	 *
	 * @return void
	 */
	abstract protected function normalizeKeys();
}