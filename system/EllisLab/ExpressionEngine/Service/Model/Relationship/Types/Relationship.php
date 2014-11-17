<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship\Types;

use EllisLab\ExpressionEngine\Service\AliasService;
use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relationship
 *
 * Abstract relationship type.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship\Types
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Relationship {

	public $name;
	public $model;
	public $type;
	public $key;
	public $to_key;
	public $is_parent;

	public $from;
	public $to;

	protected $factory;

	/**
	 * @param Factory $factory  model factory
	 * @param String  $from     name of the source model
	 * @param String  $to       name of target model
	 * @param String  $name     name of the relationship
	 * @param String  $info     array of relationship data
	 */
	public function __construct($factory, $from, $to, $name, $info)
	{
		$this->to = $to;
		$this->from = $from;
		$this->factory = $factory;

		$this->is_collection = (substr($this->type, -4) == 'many');

		$keys = array(
			'name'      => $name,
			'model'     => $to,
			'type'      => NULL,
			'key'       => NULL,
			'to_key'    => NULL,
			'weak'      => FALSE,
			'is_parent' => FALSE
		);

		// make sure all the keys are there - as null if not given
		$info = array_merge($keys, $info);

		foreach ($info as $key => $value)
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
	 *
	 */
	public function getInverse()
	{
		// TODO law of demeter violation
		$relationships = $this->factory
			->getRelationshipManager()
			->getRelationships($this->to);

		foreach ($relationships as $name => $edge)
		{
			if ($edge->key == $this->to_key &&
				$edge->to_key == $this->key &&
				$edge->type == $this->inverse)
			{
				return $edge;
			}
		}

		return NULL;
	}

	/**
	 *
	 */
	public function eagerQuery($query, $from_alias, $to_alias)
	{
		$query->joinModel($to_alias)
			->on($from_alias)
			->where($this->key, '=', $this->to_key);
	}

	/**
	 *
	 */
	public function lazyQuery($query, $parent_object, $to_alias)
	{
		$query->setFrom($to_alias);

		$builder = $query->getBuilder();

		$builder->filter("{$to_alias}.{$this->to_key}", $parent_object->{$this->key});
		return;

		$query->applyFilter(
			$query->rewriteProperty("{$to_alias}.{$this->to_key}"),
			'=',
			$parent_object->{$this->key}
		);
	}

	public function sync($from_instance, $to_collection)
	{
		// only implemented by many-to-many, all the others are automatic
		// this is here so we don't need to check in the model
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
	 * Code needed to disconnect the ids between instances of two models of this
	 * relationship type.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	abstract public function disconnect($from_instance, $to_model_or_collection);

	/**
	 * Determine whether the edge accepts a given action.
	 *
	 * Actions include things such as: 'add', 'set', 'remove', 'delete', etc.
	 *
	 * @param $action Name of the action
	 * @return boolean
	 */
	abstract public function assertAcceptsAction($action);

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
