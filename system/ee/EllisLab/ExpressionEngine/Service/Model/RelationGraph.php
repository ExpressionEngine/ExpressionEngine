<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model;

use EllisLab\ExpressionEngine\Service\Model\Relation\Relation;

/**
 * Model Service Relation Graph
 *
 * Relations are the edges of the relationship graph. The node of the graph
 * are static (classes, not objects). Usually relations are lazily created
 * on the graph one model class at a time as the information is needed. We
 * cache all relation information, so over the time of a request we build up
 * a more and more complete view of the graph. A full subgraph can also be
 * constructed on the fly to do things like cascade deletes.
 */
class RelationGraph {

	/**
	 * @var EllisLab\ExpressionEngine\Service\Model\Registry
	 */
	private $registry;

	/**
	 * @var EllisLab\ExpressionEngine\Service\Model\Datastore
	 */
	private $datastore;

	/**
	 * @var Array of one sided model dependencies (e.g. [addon:model => [ee:model1, ee:model2])
	 */
	private $foreign_models;

	/**
	 * @var Array of Relation objects [modelname => [relationshipname => relation]]
	 */
	private $relations = array();

	/**
	 * @param $datastore EllisLab\ExpressionEngine\Service\Model\Datastore
	 * @param $registry EllisLab\ExpressionEngine\Service\Model\Registry
	 * @param Array $foreign_models Map of one sided model dependencies (e.g. [addon:model => [ee:model1, ee:model2])
	 */
	public function __construct(DataStore $datastore, Registry $registry, array $foreign_models)
	{
		$this->datastore = $datastore;
		$this->registry = $registry;
		$this->foreign_models = $foreign_models;
	}

	/**
	 * Get a relation object for a given model and relationship name
	 */
	public function get($model_name, $name)
	{
		$relations = $this->getAll($model_name);
		return $relations[$name];
	}

	/**
	 * Get all relations for a given model name
	 */
	public function getAll($model_name)
	{
		$prefix = $this->registry->getPrefix($model_name);

		if (strpos($model_name, $prefix) !== 0)
		{
			$model_name = $prefix.':'.$model_name;
		}

		if (isset($this->relations[$model_name]))
		{
			return $this->relations[$model_name];
		}

		$relations = array();
		$relationships = $this->fetchRelationships($model_name);

		// create the easily defined ones
		foreach ($relationships as $name => $info)
		{
			$relation = $this->makeRelation($model_name, $name);

			$relations[$name] = $relation;
		}

		$this->relations[$model_name] = $relations;

		// add any relations that are declared on foreign models
		// for example, Member will grab forum relationships here
		foreach ($this->foreign_models as $foreign_model => $native_models)
		{
			if ( ! $this->registry->isEnabled($foreign_model))
			{
				continue;
			}

			if (in_array($model_name, $native_models))
			{
				$foreign_relations = $this->getAll($foreign_model);

				foreach ($foreign_relations as $name => $relation)
				{
					if ($relation->getTargetModel() == $model_name)
					{
						$inverse = $relation->getInverse();
						$this->relations[$model_name][$inverse->getName()] = $inverse;
					}
				}
			}
		}

		return $this->relations[$model_name];
	}

	/**
	 * Get inverse of a relation.
	 *
	 * Do not call this directly, always call getInverse() on the relation
	 */
	public function getInverse(Relation $relation)
	{
		$model = $relation->getTargetModel();
		$source = $relation->getSourceModel();

		$prefix = $this->registry->getPrefix($model);

		if (strpos($model, $prefix) !== 0)
		{
			$model = $prefix.':'.$model;
		}

		// grab all potential options
		$relations = $this->getAll($model);

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

	/**
	 * Create the foreign inverse. Do not call this directly, always
	 * use `$relation->getInverse()`
	 */
	public function makeForeignInverse($relation)
	{
		$to_model = $relation->getTargetModel();
		$options = $relation->getInverseOptions();

		$options['model'] = $relation->getSourceModel();

		$prefix = $this->registry->getPrefix($relation->getSourceModel());
		$name = $options['name'];

		if (strpos($name, $prefix) !== 0)
		{
			$name = $prefix.':'.$name;
		}


		$prefix = $this->registry->getPrefix($to_model);

		if (strpos($to_model, $prefix) !== 0)
		{
			$to_model = $prefix.':'.$to_model;
		}

		unset($options['name']);

 		return $this->relations[$to_model][$name] = $this->makeRelation($to_model, $name, $options);
	}

	/**
	 * Create a new relation object, only should be called if one doesn't
	 * already exist.
	 *
	 * @param String $model Model where the relation lives
	 * @
	 */
	public function makeRelation($model, $name, $options = NULL)
	{
		$options = $options ?: $this->prepareRelationshipData($model, $name);

		$type = ucfirst($options['type']);
		$class = __NAMESPACE__."\\Relation\\{$type}";

		if ( ! class_exists($class))
		{
			throw new \Exception("Unknown relationship type {$type} in {$model}");
		}

		$from_reader = $this->registry->getMetaDataReader($model);
		$to_reader = $this->registry->getMetaDataReader($options['model']);

		$relation = new $class($from_reader, $to_reader, $name, $options);
		$relation->setDataStore($this->datastore);

		return $relation;
	}

	/**
	 *
	 */
	private function prepareRelationshipData($model, $name)
	{
		$relationship = $this->fetchRelationship($model, $name);

		$to_model = isset($relationship['model']) ? $relationship['model'] : $name;
		$as_defined_to = $to_model;

		if (strpos($to_model, ':') == 0)
		{
			$to_model = $this->registry->getPrefix($model).':'.$to_model;
		}

		if ( ! $this->registry->modelExists($to_model))
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
			'model' => $to_model
		);

		return array_replace($defaults, $relationship, $required);
	}

	/**
	 * Fetch a given entry in the relationship meta array.
	 *
	 * @param String $model_name Model alias
	 * @param String $name Relationship name to fetch
	 * @return array of relationships data
	 */
	private function fetchRelationship($model, $name)
	{
		$relationships = $this->fetchRelationships($model);

		if ( ! array_key_exists($name, $relationships))
		{
			throw new \Exception("Relationship {$name} not found in model {$model}");
		}

		return $relationships[$name];
	}

	/**
	 * Fetch the entire relationship meta array.
	 *
	 * @param String $model_name Model alias
	 * @return array of relationships as defined on the model
	 */
	private function fetchRelationships($model_name)
	{
		return $this->registry->getMetaDataReader($model_name)->getRelationships();
	}
}
