<?php

namespace EllisLab\ExpressionEngine\Service\Model;

use EllisLab\ExpressionEngine\Service\Model\Relation\Relation;

class RelationGraph {

	private $relations = array();

	private $datastore;
	private $default_prefix;
	private $enabled_prefixes;
	private $foreign_models;

	/**
	 *
	 */
	public function __construct(DataStore $datastore, $default_prefix, $enabled_prefixes, $foreign_models)
	{
		$this->datastore = $datastore;
		$this->default_prefix = $default_prefix;
		$this->enabled_prefixes = $enabled_prefixes;
		$this->foreign_models = $foreign_models;
	}

	/**
	 *
	 */
	public function get($model_name, $name)
	{
		$relations = $this->getAll($model_name);
		return $relations[$name];
	}

	/**
	 *
	 */
	public function getAll($model_name)
	{
		$prefix = $this->getPrefix($model_name);

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

		foreach ($relationships as $name => $info)
		{
			$relations[$name] = $this->makeRelation($model_name, $name);
		}

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
					if ( ! isset($ship['inverse']))
					{
						continue;
					}

					if ($ship['model'] == $model_name)
					{
						$relation = $this->makeRelation($model, $name);
						$inverse = $relation->getInverse();
						$relations[$inverse->getName()] = $inverse;
					}
				}
			}
		}

		return $this->relations[$model_name] = $relations;
	}

	/**
	 *
	 */
	public function getInverse(Relation $relation)
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
	 *
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

		$from_reader = $this->datastore->getMetaDataReader($model);
		$to_reader = $this->datastore->getMetaDataReader($options['model']);

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
			$to_model = $this->getPrefix($model).':'.$to_model;
		}

		if ( ! $this->datastore->modelExists($to_model))
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

		$options = array_replace($defaults, $relationship, $required);

		if (isset($options['pivot']))
		{
			$options['pivot'] = $this->processPivot($options);
		}

		return $options;
	}

	/**
	 *
	 */
	private function getForeignInverse($relation, $to_model)
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

		return $this->makeRelation($to_model, $name, $options);
	}

	/**
	 * Process pivot information
	 *
	 * In a model the pivot key can either be an array or a table name.
	 * If it is a table name, then the lhs and rhs keys must equal the pk's
	 * of the two models
	 */
	private function processPivot($options)
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

	/**
	 *
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
	 *
	 */
	private function fetchRelationships($model)
	{
		return $this->datastore->getMetaDataReader($model)->getRelationships();
	}

	/**
	 *
	 */
	private function getPrimaryKey($model)
	{
		return $this->datastore->getMetaDataReader($model)->getPrimaryKey();
	}

	/**
	 *
	 */
	private function modelIsEnabled($model_name)
	{
		return in_array(strstr($model_name, ':', TRUE), $this->enabled_prefixes);
	}

	private function getPrefix($model)
	{
		if (strpos($model, ':'))
		{
			return strstr($model, ':', TRUE);
		}

		return $this->default_prefix;
	}
}
