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

	protected $db;
	protected $aliases;
	protected $default_prefix;
	protected $enabled_prefixes;
	protected $metadata = array();
	protected $relations = array();

	/**
	 * @param $db EllisLab\ExpressionEngine\Service\Database\Database
	 * @param $aliases Array of model aliases
	 */
	public function __construct(Database $db, $aliases, $foreign_models, $default_prefix, $enabled_prefixes)
	{
		$this->db = $db;
		$this->aliases = $aliases;
		$this->default_prefix = $default_prefix;
		$this->foreign_models = $foreign_models;
		$this->enabled_prefixes = $enabled_prefixes;
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
		if ($name instanceOf Model)
		{
			$object = $name;
			$name = $object->getName();
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

		$model->setName($name);
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
			$assoc = $relation->createAssociation($model);
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

		$foreigns = array();

		foreach ($this->foreign_models as $model => $dependencies)
		{
			if ( ! $this->modelIsEnabled($model))
			{
				continue;
			}

			if (in_array($model_name, $dependencies))
			{
				$ships = $this->fetchRelationships($model);

				foreach ($ships as $name => $ship)
				{
					if (isset($ship['model']) && $ship['model'] == $model_name)
					{
						$relation = $this->getRelation($model, $name);
						$inverse = $relation->getInverse();

						$relations[$inverse->getName()] = $inverse;
					}
				}
			}
		}

		return $relations;
	}

	protected function modelIsEnabled($model_name)
	{
		return in_array(strstr($model_name, ':', TRUE), $this->enabled_prefixes);
	}

	public function getInverseRelation(Relation $relation)
	{
		$model = $relation->getTargetModel();
		$source = $relation->getSourceModel();

		$prefix = $this->getPrefix($model);

		if (strpos($model, $prefix) !== 0)
		{
			$model = $prefix.':'.$model;
		}

		if (isset($this->foreign_models[$source]))
		{
			if (in_array($model, $this->foreign_models[$source]))
			{
				return $this->getForeignInverse($relation, $model);
			}
		}

		$relations = $this->getAllRelations($model);

		// todo check for more than one match
		// provide a good error for a missing match

		foreach ($relations as $name => $possibility)
		{
			if ($possibility->getTargetModel() == $relation->getSourceModel())
			{
				// todo also check if valid reverse type
				if (array_reverse($possibility->getKeys()) == $relation->getKeys())
				{
					$pivot1 = $relation->getPivot();
					$pivot2 = $possibility->getPivot();

					if (count($pivot1) != count($pivot2))
					{
						// todo error?
						continue;
					}
					elseif (count($pivot1) > 0)
					{
						if (($pivot1['table'] != $pivot2['table']) ||
							($pivot1['left'] != $pivot2['right']) ||
							($pivot1['right'] != $pivot2['left']))
						{
							continue;
						}
					}

					return $possibility;
				}
			}
		}


		$name = $relation->getName();
		$from = $relation->getSourceModel();
		$type = substr(strrchr(get_class($relation), '\\'), 1);

		throw new \Exception("Missing Relationship. Model <i>{$from}</i> {$type}
			model <i>{$model}</i> which it calls '{$name}', but no available
			connection from <i>{$model}</i> to <i>{$from}</i> was found."
		);
	}

	protected function getForeignInverse($relation, $to_model)
	{
		$options = $relation->getInverseOptions();
		$options['model'] = $relation->getSourceModel();

		$prefix = $this->getPrefix($relation->getSourceModel());
		$name = $options['name'];

		if (strpos($name, $prefix) !== 0)
		{
			$name = $prefix.':'.$name;
		}

		unset($options['name']);

		if (array_key_exists($to_model.'_'.$name, $this->relations))
		{
			return $this->relations[$to_model.'_'.$name];
		}

		return $this->relations[$to_model.'_'.$name] = $this->newRelation($to_model, $name, $options);
	}

	public function getRelation($model, $name)
	{
		if (array_key_exists($model.'_'.$name, $this->relations))
		{
			return $this->relations[$model.'_'.$name];
		}

		$options = $this->prepareRelationshipData($model, $name);

		return $this->relations[$model.'_'.$name] = $this->newRelation($model, $name, $options);
	}

	protected function newRelation($model, $name, $options)
	{
		$type = ucfirst($options['type']);
		$class = __NAMESPACE__."\\Relation\\{$type}";

		if ( ! class_exists($class))
		{
			throw new \Exception("Unknown relationship type {$type} in {$model}");
		}

		$from_reader = $this->getMetaDataReader($model);
		$to_reader = $this->getMetaDataReader($options['model']);

		$relation = new $class($from_reader, $to_reader, $name, $options);
		$relation->setDataStore($this);

		return $relation;
	}

	protected function prepareRelationshipData($model, $name)
	{
		$relationship = $this->fetchRelationship($model, $name);

		$to_model = isset($relationship['model']) ? $relationship['model'] : $name;
		$as_defined_to = $to_model;

		if (strpos($to_model, ':') == 0)
		{
			$to_model = $this->getPrefix($model).':'.$to_model;
		}

		if ( ! isset($this->aliases[$to_model]))
		{
			throw new \Exception('Unknown model "'.$as_defined_to.'". Used in model "'.$model.'" for a relationship called "'.$name.'".');
		}

		$defaults = array(
			'from_key' => NULL,
			'from_table' => NULL,
			'to_key' => NULL,
			'to_table' => NULL
		);

		$required = array(
			'model' => $to_model,
			'from_primary_key' => $this->getPrimaryKey($model),
			'to_primary_key' => $this->getPrimaryKey($to_model)
		);

		$options = array_replace($defaults, $relationship, $required);

		if (isset($options['pivot']))
		{
			$options['pivot'] = $this->processPivot($options);
		}

		return $options;
	}

	// pivot can either be an array or a table name.
	// if it is a table name, then the lhs and rhs keys must
	// equal the pk's of the two models
	protected function processPivot($options)
	{
		$pivot = $options['pivot'];

		$defaults = array(
			'left' => $options['from_primary_key'],
			'right' => $options['to_primary_key']
		);

		if ( ! is_array($pivot))
		{
			$pivot = array('table' => $pivot);
		}

		return $pivot + $defaults;
	}

	protected function fetchRelationship($model, $name)
	{
		$relationships = $this->fetchRelationships($model);

		if ( ! array_key_exists($name, $relationships))
		{
			throw new \Exception("Relationship {$name} not found in model {$model}");
		}

		return $relationships[$name];
	}

	protected function fetchRelationships($model)
	{
		$class = $this->expandModelAlias($model);
		$relationships = $class::getMetaData('relationships');

		return $relationships ?: array();
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
