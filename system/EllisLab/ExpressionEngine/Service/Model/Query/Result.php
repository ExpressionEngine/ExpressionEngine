<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Factory as ModelFactory;

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
 * ExpressionEngine Query Result Class
 *
 * A class containing the result of a database query and providing behavior
 * allowing that data to be parsed into models.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Result {

	private $factory;

	private $db_result;
	private $model_index = array();

	public function __construct(ModelFactory $factory, Builder $builder, array $result_array)
	{
		$this->factory = $factory;
		$this->builder = $builder;
		$this->db_result = $result_array;
	}

	/**
	 * Get the first result
	 */
	public function first()
	{
		if ( ! count($this->db_result))
		{
			return NULL;
		}

		$models = $this->parseResult();

		return $models[0];
	}

	/**
	 * Get all results as a collection
	 */
	public function collection()
	{
		return new Collection($this->parseResult());
	}


	protected function parseResult()
	{
		$results = array();
		$result_index = array();

		// Create a list of children that were asked for. We need to set
		// these even if they came back with no results so that we don't
		// end up lazily requerying everything.
		$ensure_set = array();

		foreach ($this->builder->getReferences() as $key => $ref)
		{
			if (isset($ref->parent))
			{
				$parent_alias = $ref->parent->alias;

				if ( ! isset($ensure_set[$parent_alias]))
				{
					$ensure_set[$parent_alias] = array();
				}

				$ensure_set[$parent_alias][] = $ref;
			}
		}

		// Prefil if this was on an existing object
		$object = $this->builder->getModelObject();
		$existing = array();

		if (isset($object))
		{
			$alias = $this->builder->getRootAlias();
			$existing[$alias] = array();

			$primary_key_name = $this->factory->getMetaData($this->builder->getRootModel(), 'primary_key');

			$result_index[$object->getId()] = TRUE;

			$existing[$alias][$primary_key_name] = $object->getId();
			$this->model_index[$alias][$object->getId()] = $object;
		}


		foreach ($this->db_result as $row)
		{
			$row_data = $existing;

			foreach ($row as $name => $value)
			{
				list($alias, $field) = explode('__', $name);

				if ( ! isset($row_data[$alias]))
				{
					$row_data[$alias] = array();
				}

				$row_data[$alias][$field] = $value;
			}

			foreach ($row_data as $alias_string => $values)
			{
				// If this is an empty model that happened to have been grabbed due to the join,
				// move on and don't do anything.
				// todo can just consume the referencechain if we don't need the builder elsewhere
				$model_ref = $this->builder->getAliasReference($alias_string);

				$primary_key_name = $this->factory->getMetaData($model_ref->model, 'primary_key');

				if ( ! isset($values[$primary_key_name]))
				{
					unset($row_data[$alias_string]);
					continue;
				}

				$primary_key = $values[$primary_key_name];

				// already created?
				if (isset($this->model_index[$model_ref->alias][$primary_key]))
				{
					continue;
				}

				$model = $this->createResultModel($model_ref, $values);

				// Prefill relationships with blanks. If they end up actually
				// being set they will be re-filled below.
				if (isset($ensure_set[$model_ref->alias]))
				{
					foreach ($ensure_set[$model_ref->alias] as $child_ref)
					{
						$edge = $child_ref->connecting_edge;
						$model->fillRelated($edge->name);
					}
				}

				// Collect root models
				if ($this->isRootModel($model_ref))
				{
					if ( ! isset($result_index[$primary_key]))
					{
						$results[] = $model;
						$result_index[$primary_key] = TRUE;
					}

					continue;
				}

				$parent_ref = $model_ref->parent;
				$parent_model = $parent_ref->model;

				$primary_key = $this->factory->getMetaData($parent_model, 'primary_key');
				$parent_id = $row_data[$parent_ref->alias][$primary_key];

				$parent_model = $this->model_index[$parent_ref->alias][$parent_id];

				if ($parent_model === NULL)
				{
					throw new \Exception('Missing model parent!');
				}

				// Fill the relationship
				$edge = $this->findParentRelationship($model_ref);

				if ( ! $parent_model->hasRelated($edge->name, $primary_key))
				{
					$parent_model->fillRelated($edge->name, $model);
				}

				// Check if we can safely set the reverse case.
				// If the query was TemplateGroup with Templates, then all the
				// templates can have their template group set safely.
				// If the query was Template with TemplateGroup, then we cannot
				// set the templates for the template group, because we cannot
				// be sure we queried all of them.
				if ($edge->is_parent)
				{
					$reverse = $edge->getInverse();

					if ($reverse && ! $reverse->is_collection)
					{
						$model->fillRelated($reverse->name, $parent_model);
					}
				}
			}
		}

		return $results;
	}


	/**
	 *
	 */
	protected function createResultModel($alias, $data)
	{
		$model_name = $alias->model;

		$model = $this->factory->make($model_name);
		$model->fill($data);

		$primary_key_name = $this->factory->getMetaData($model_name, 'primary_key');
		$primary_key = $data[$primary_key_name];

		$alias_name = $alias->alias;

		if ( ! isset($this->model_index[$alias_name]))
		{
			$this->model_index[$alias_name] = array();
		}

		$this->model_index[$alias_name][$primary_key] = $model;

		return $this->model_index[$alias_name][$primary_key];
	}

	/**
	 *
	 */
	protected function findParentRelationship($ref)
	{
		return $ref->connecting_edge;
	}

	/**
	 *
	 */
	protected function isRootModel($alias)
	{
		return ( ! isset($alias->parent));
	}

}
