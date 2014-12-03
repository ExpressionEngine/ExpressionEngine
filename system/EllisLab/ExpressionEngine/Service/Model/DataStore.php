<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use EllisLab\ExpressionEngine\Service\Model\Query\Builder;

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
 * ExpressionEngine DataStore
 *
 * This is the backend for all model interactions. It should never be exposed
 * directly to any code outside of this namespace, including userspace models.
 * The only way to interact with it should be through the model frontend.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class DataStore {

	protected $db;
	protected $aliases;

	/**
	 *
	 */
	public function __construct($db, $alias_config_path)
	{
		$this->db = $db;
		// todo move to config service
		$this->aliases = include $alias_config_path;
	}

	/**
	 * @param Object|String $name      The name of a model or an existing
	 *                                 model instance
	 * @param Frontend      $frontend  A frontend instance. The datastore
	 *                                 doesn't care about the frontend, but
	 *                                 the model and associations need it, so
	 *                                 we pass it in to keep things properly
	 *                                 isolated.
	 * @param Array         $data      The initial data to set on the object.
	 *                                 This will be marked as dirty! Use fill()
	 *                                 if you need clean data (i.e. from db).
	 */
	public function make($name, Frontend $frontend, array $data = array())
	{
		if ($name instanceOf Model)
		{
			$object = $name;
			$name = $this->getModelAlias(get_class($object));
		}
		else
		{
			$class = $this->expandModelAlias($name);
			$model = new $class($data);
		}

		$model->setName($name);
		$model->setFrontend($frontend);

		$this->initializeAssociationsOn($model);

		return $model;
	}

	/**
	 *
	 */
	public function get($name)
	{
		$object = NULL;

		if ($name instanceOf Model)
		{
			$object = $name;
			$name = $object->getName();
		}

		$builder = new Builder($name);

		$builder->setExisting($object);
		$builder->setDataStore($this);

		return $builder;
	}

	/**
	 *
	 */
	public function rawQuery()
	{
		return $this->db;
	}

	/**
	 *
	 */
	public function getMetaDataReader($name)
	{
		$class = $this->expandModelAlias($name);

		return new MetaDataReader($name, $class);
	}

	/**
	 *
	 */
	protected function initializeAssociationsOn(Model $model)
	{
		$result = array();
		$relations = $this->getAllRelations($model->getName());

		foreach ($relations as $name => $relation)
		{
			$assoc = $relation->createAssociation($model);

			// todo move these into relation?
			$assoc->setRelation($relation);
			$model->setAssociation($name, $assoc);
		}
	}

	/**
	 *
	 */
	public function getAllRelations($model_name)
	{
		$from_reader = $this->getMetaDataReader($model_name);
		$relationships = $from_reader->getRelationships();

		$relations = array();

		foreach ($relationships as $name => $info)
		{
			$relations[$name] = $this->getRelation($model_name, $name);
		}

		return $relations;
	}

	/**
	 * TODO stinky
	 */
	public function getRelation($model_name, $name)
	{
		// TODO cache the options array.
		// we can recreate the readers easily and cheaply, but
		// there can be a lot involved in the options.

		$from_reader = $this->getMetaDataReader($model_name);
		$relationships = $from_reader->getRelationships();

		if ( ! isset($relationships[$name]))
		{
			// TODO use name as the model name and attempt to
			// look it up in the other direction
			throw new \Exception("Relationship {$name} not found in model definition.");
		}

		$options = $relationships[$name];

		$options = array_merge(
			array(
				'model' => $name,

				'from_key' => NULL,
				'from_table' => NULL,

				'to_key' => NULL,
				'to_table' => NULL
			),
			$options
		);

		$to_reader = $this->getMetaDataReader($options['model']);

		$options['from_primary_key'] = $from_reader->getPrimaryKey();
		$options['to_primary_key'] = $to_reader->getPrimaryKey();

		// pivot can either be an array or a gateway name.
		// if it is a gateway, then the lhs and rhs keys must
		// equal the pk's of the two models
		if (isset($options['pivot']))
		{
			$pivot = $options['pivot'];

			if ( ! is_array($pivot))
			{
				$gateway_tables = $from_reader->getTableNamesByGateway();
				$table = $gateway_tables[$pivot];

				$options['pivot'] = array(
					'table' => $table,
					'left'  => $options['from_primary_key'],
					'right' => $options['to_primary_key']
				);
			}
		}

		$type = $options['type'];
		$class = __NAMESPACE__."\\Relation\\{$type}";

		$relation = new $class($from_reader, $to_reader, $name, $options);
		$relation->setDataStore($this);

		return $relation;
	}

	/**
	 *
	 */
	public function fetchQuery(Builder $qb)
	{
		return $this->runQuery('Select', $qb);
	}

	/**
	 *
	 */
	public function insertQuery(Builder $qb)
	{
		return $this->runQuery('Insert', $qb);
	}

	/**
	 *
	 */
	public function updateQuery(Builder $qb)
	{
		return $this->runQuery('Update', $qb);
	}

	/**
	 *
	 */
	public function deleteQuery(Builder $qb)
	{
		return $this->runQuery('Delete', $qb);
	}

	// helpers

	/**
	 *
	 */
	protected function runQuery($name, Builder $qb)
	{
		$class = __NAMESPACE__."\\Query\\{$name}";

		$worker = new $class($this, $qb);
		return $worker->run();
	}

	/**
	 *
	 */
	protected function getModelAlias($class)
	{
		$classes = array_flip($this->aliases[$name]);
		return $classes[$name];
	}

	/**
	 *
	 */
	protected function expandModelAlias($name)
	{
		if ( ! isset($this->aliases[$name]))
		{
			if ( ! class_exists($name))
			{
				throw new \Exception("Unknown model: {$name}");
			}

			return $name;
		}

		return $this->aliases[$name];
	}
}