<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use Closure;

use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use EllisLab\ExpressionEngine\Service\Model\Relation\Relation;
use EllisLab\ExpressionEngine\Service\Database\Database;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine DataStore
 *
 * This is the backend for all model interactions. It should never be exposed
 * directly to any code outside of this namespace. This includes no access from
 * userspace models. The only way to interact with it should be through the
 * model facade.
 *
 * @package		ExpressionEngine
 * @category	Service
 * @subpackage	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class DataStore {

	private $db;
	private $graph;
	private $config;

	protected $aliases;
	protected $default_prefix;
	protected $metadata = array();

	/**
	 * @param $db EllisLab\ExpressionEngine\Service\Database\Database
	 * @param $config EllisLab\ExpressionEngine\Service\Model\Configuration
	 */
	public function __construct(Database $db, Configuration $config)
	{
		$this->db = $db;
		$this->config = $config;

		$this->graph = new RelationGraph(
			$this,
			$config->getDefaultPrefix(),
			$config->getEnabledPrefixes(),
			$config->getModelDependencies()
		);

		$this->aliases = $config->getModelAliases();
		$this->default_prefix = $config->getDefaultPrefix();
	}

	/**
	 * @param Object|String $name      The name of a model or an existing
	 *                                 model instance
	 * @param Facade      $facade      A facade instance. The datastore
	 *                                 doesn't care about the facade, but
	 *                                 the model and associations need it, so
	 *                                 we pass it in to keep things properly
	 *                                 isolated.
	 * @param Array         $data      The initial data to set on the object.
	 *                                 This will be marked as dirty! Use fill()
	 *                                 if you need clean data (i.e. from db).
	 */
	public function make($name, Facade $facade, array $data = array())
	{
		$is_object = ($name instanceOf Model);

		if ($is_object)
		{
			$model = $name;
			$name = $model->getName();
		}
		else
		{
			$model = $this->newModelFromAlias($name);
		}

		$prefix = $this->getPrefix($name);

		if (strpos($name, $prefix) !== 0)
		{
			$name = $prefix.':'.$name;
		}

		if (count($data))
		{
			$model->set($data);
		}

		if ( ! $is_object)
		{
			$model->setName($name);
		}

		$model->setFacade($facade);

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
		$relations = $this->getAllRelations($model->getName());

		foreach ($relations as $name => $relation)
		{
			$assoc = $relation->createAssociation();
			$model->setAssociation($name, $assoc);
		}
	}

	protected function modelIsEnabled($model_name)
	{
		$prefix = strstr($model_name, ':', TRUE);

		return $this->config->isEnabledPrefix($prefix);
	}

	/**
	 * Get all relations for a model
	 *
	 * @param String $model_name  Name of the model
	 * @return Array of relations
	 */
	public function getAllRelations($model_name)
	{
		return $this->graph->getAll($model_name);
	}

	public function getInverseRelation(Relation $relation)
	{
		return $this->graph->getInverse($relation);
	}

	public function getRelation($model, $name)
	{
		return $this->graph->get($model, $name);
	}

	protected function getPrimaryKey($model)
	{
		$class = $this->expandModelAlias($model);
		return $class::getMetaData('primary_key');
	}

	protected function getPrefix($model)
	{
		if (strpos($model, ':'))
		{
			return strstr($model, ':', TRUE);
		}

		return $this->default_prefix;
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

	/**
	 * Check if a model exists given an alias
	 *
	 * @param String $alias Model alias (with prefix)
	 * @return bool Exists?
	 */
	public function modelExists($alias)
	{
		return array_key_exists($alias, $this->aliases);
	}

	/**
	 * Create a model instance from the di object
	 *
	 * @param String $name Model name
	 * @return
	 */
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

// EOF
