<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;

use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use EllisLab\ExpressionEngine\Service\Database\Database;

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
	protected $default_prefix;
	protected $metadata = array();

	/**
	 * @param $db \CI_DB
	 * @param $aliases Array of model aliases
	 */
	public function __construct(Database $db, $aliases, $default_prefix)
	{
		$this->db = $db;
		$this->aliases = $aliases;
		$this->default_prefix = $default_prefix;
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
			$name = $object->getName();
		}
		else
		{
			$model = $this->newModelFromAlias($name);
		}

		if (count($data))
		{
			$model->set($data);
		}

		$model->setName($name);
		$model->setFrontend($frontend);

		$this->initializeAssociationsOn($model);

		return $model;
	}

	/**
	 * Create a query
	 *
	 * @param String $name  Name of the model to query on
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
	 * Create a raw query
	 *
	 * @return Object \CI_DB
	 */
	public function rawQuery()
	{
		return $this->db->newQuery();
	}

	/**
	 * Create a metaDataReader
	 *
	 * @param String $name  Model to read metadata from
	 * @return Object MetaDataReader
	 */
	public function getMetaDataReader($name)
	{
		$class = $this->expandModelAlias($name);

		if ( ! isset($this->metadata[$class]))
		{
			$this->metadata[$class] = new MetaDataReader($name, $class);
		}

		return $this->metadata[$class];
	}

	/**
	 * Prep the model associations
	 *
	 * @param Model $model  Model to initialize
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
	 * Get all relations for a model
	 *
	 * @param String $model_name  Name of the model
	 * @return Array of relations
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
		$from_reader = $this->getMetaDataReader($model_name);
		$relationships = $from_reader->getRelationships();

		if ( ! isset($relationships[$name]))
		{
			// TODO use name as the model name and attempt to
			// look it up in the other direction
			throw new \Exception("Relationship {$name} not found in model definition.");
		}

		$options = array_merge(
			array(
				'model' => $name,

				'from_key' => NULL,
				'from_table' => NULL,

				'to_key' => NULL,
				'to_table' => NULL
			),
			$relationships[$name]
		);

		$to_reader = $this->getMetaDataReader($options['model']);

		$options['from_primary_key'] = $from_reader->getPrimaryKey();
		$options['to_primary_key'] = $to_reader->getPrimaryKey();

		// pivot can either be an array or a table name.
		// if it is a table name, then the lhs and rhs keys must
		// equal the pk's of the two models
		if (isset($options['pivot']))
		{
			$pivot = $options['pivot'];

			if ( ! is_array($pivot))
			{
				$gateway_tables = $from_reader->getTableNamesByGateway();
				$table = $gateway_tables[$pivot];

				$options['pivot'] = array(
					'table' => $table
				);
			}
		}

		$type = ucfirst($options['type']);
		$class = __NAMESPACE__."\\Relation\\{$type}";

		if ( ! class_exists($class))
		{
			throw new \Exception("Unknown relationship type {$type} in {$model_name}");
		}

		$relation = new $class($from_reader, $to_reader, $name, $options);
		$relation->setDataStore($this);

		return $relation;
	}

	/**
	 * Run a select query
	 *
	 * @param Builder $qb The query builder describing the query
	 */
	public function selectQuery(Builder $qb)
	{
		return $this->runQuery('Select', $qb);
	}

	/**
	 * Run an insert query
	 *
	 * @param Builder $qb The query builder describing the query
	 */
	public function insertQuery(Builder $qb)
	{
		return $this->runQuery('Insert', $qb);
	}

	/**
	 * Run an update query
	 *
	 * @param Builder $qb The query builder describing the query
	 */
	public function updateQuery(Builder $qb)
	{
		return $this->runQuery('Update', $qb);
	}

	/**
	 * Run a delete query
	 *
	 * @param Builder $qb The query builder describing the query
	 */
	public function deleteQuery(Builder $qb)
	{
		return $this->runQuery('Delete', $qb);
	}

	/**
	 * Run a count query
	 *
	 * @param Builder $qb The query builder describing the query
	 */
	public function countQuery(Builder $qb)
	{
		return $this->runQuery('Count', $qb);
	}

	/**
	 * Run a given query strategy
	 *
	 * @param String $name The name of the strategy
	 * @param Builder $qb  The query builder describing the query
	 */
	protected function runQuery($name, Builder $qb)
	{
		$class = __NAMESPACE__."\\Query\\{$name}";

		$worker = new $class($this, $qb);
		return $worker->run();
	}

	protected function newModelFromAlias($name)
	{
		$class = $this->expandModelAlias($name);

		if ($class instanceOf Closure)
		{
			$model = $class();
		}
		else
		{
			$model = new $class();
		}

		return $model;
	}

	/**
	 * Given a model alias, get the class name. If a class name
	 * is passed and no alias is found, return that class name.
	 *
	 * @param String $name The alias name to look up
	 * @return String The class name
	 */
	protected function expandModelAlias($name)
	{
		if ( ! strpos($name, ':'))
		{
			$name = $this->default_prefix.':'.$name;
		}

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