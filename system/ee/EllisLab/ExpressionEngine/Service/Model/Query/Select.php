<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use LogicException;

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
 * ExpressionEngine Select Query
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Select extends Query {

	protected $root_alias = '';
	protected $aliases = array();
	protected $relations = array();
	protected $model_fields = array();

	/**
	 *
	 */
	public function run()
	{
		$query = $this->buildQuery();

		return new Result(
			$this->builder,
			$query->get()->result_array(),
			$this->aliases,
			$this->relations
		);
	}

	/**
	 *
	 */
	protected function buildQuery()
	{
		$builder = $this->builder;
		$query = $this->store->rawQuery();

		$from = $builder->getFrom();
		list($from, $alias) = $this->splitAlias($from);

		$this->root_alias = $alias;
		$this->selectModel($query, $from, $alias);
		$this->processWiths($query, $from, $alias);

		// lazy load adds a where condition
		foreach ($builder->getLazyConstraints() as $constraint)
		{
			list($relation, $parent) = $constraint;

			$relation->modifyLazyQuery($query, $parent, $alias);
		}

		// filters add more where conditions
		// We group them here since additional tables are sometimes added with
		// a where condition instead of a join
		$filters = $builder->getFilters();

		if ( ! empty($filters))
		{
			$query->start_group();
			$this->applyFilters($query, $filters);
			$query->end_group();
		}

		// add search conditions - these are always AND'ed to the filters so
		// that we can potentially ditch them later.
		$search = $builder->getSearch();

		if ( ! empty($search))
		{
			$query->start_like_group();
			$this->applySearch($query, $search);
			$query->end_like_group();
		}

		// orders
		$this->applyOrders($query, $builder->getOrders());

		$query->limit($builder->getLimit(), $builder->getOffset());

		return $query;
	}

	/**
	 *
	 */
	protected function selectModel($query, $model, $alias, $will_join = FALSE)
	{
		// CI ar workaround. Active record is too eager in its escaping and
		// won't let us join on an aliased table that has not been created,
		// so we queue up the secondary tables until the relation creates the
		// alias for the primary one
		$queued_joins = array();

		$alias = $alias ?: $model;
		$this->storeAlias($alias, $model);

		$meta = $this->store->getMetaDataReader($model);
		$fields = $this->getFields();
		$tables = $meta->getTables();

		if ( ! isset($this->model_fields[$alias]))
		{
			$this->model_fields[$alias] = array();
		}

		reset($tables);
		$main_table = key($tables);
		$primary_key = $meta->getPrimaryKey();

		// Make sure the primary key is present in the query
		$primary_key_alias = "{$alias}.{$primary_key}";
		if ( ! empty($fields) && ! in_array($primary_key_alias, $fields))
		{
			$fields[] = $primary_key_alias;
 		}

		foreach ($tables as $table => $table_fields)
		{
			$table_alias = "{$alias}_{$table}";

			if ( ! $will_join)
			{
				$query->from("{$table} as {$table_alias}");

				if ($table != $main_table)
				{
					$query->where("{$table_alias}.{$primary_key} = {$alias}_{$main_table}.{$primary_key}", NULL, FALSE);
				}
			}
			elseif ($table != $main_table)
			{
				$queued_joins[] = array(
					"{$table} as {$table_alias}",
					"{$table_alias}.{$primary_key} = {$alias}_{$main_table}.{$primary_key}",
					'LEFT'
				);
			}

			foreach ($table_fields as $column)
			{
				// remember the name so we can translate filters and order_bys
				$this->model_fields[$alias]["{$alias}__{$column}"] = "{$table_alias}.{$column}";

				// but only select it if they did not specify fields to select
				// or they specifically chose this one to be selected
				if (empty($fields) OR in_array("{$alias}.{$column}", $fields) OR in_array("{$alias}.*", $fields))
				{
					$query->select("{$table_alias}.{$column} as {$alias}__{$column}", FALSE);
				}
			}
		}

		return $queued_joins;
	}

	/**
	 * TODO only run this once!
	 */
	protected function getFields()
	{
		$fields = array();

		foreach ($this->builder->getFields() as $field)
		{
			if (strpos($field, '.') === FALSE)
			{
				$alias = $this->root_alias;
			}
			else
			{
				list($alias, $field) = explode('.', $field, 2);
			}

			$alias = str_replace(':', '_m_', $alias);
			$fields[] = "{$alias}.{$field}";
		}

		return $fields;
	}

	/**
	 *
	 */
	protected function applyOrders($query, $orders)
	{
		foreach ($orders as $order)
		{
			list($property, $direction) = $order;

			$property = $this->translateProperty($property);

			$query->order_by($property, $direction);
		}
	}

	/**
	 *
	 */
	protected function applyFilters($query, $filters)
	{
		foreach ($filters as $filter_data)
		{
			// it's nested!
			if (count($filter_data) == 2)
			{
				list($connective, $nested) = $filter_data;

				if ($connective == 'and')
				{
					$query->start_group();
				}
				elseif ($connective == 'or')
				{
					$query->or_start_group();
				}
				else
				{
					throw new LogicException('Invalid filter group connective: '.htmlentities($connective));
				}

				$this->applyFilters($query, $nested);
				$query->end_group();
			}
			else
			{
				$this->applyFilter($query, $filter_data);
			}
		}
	}

	/**
	 *
	 */
	protected function applyFilter($query, $filter)
	{
		list($property, $operator, $value, $connective) = $filter;

		$property = $this->translateProperty($property);

		$fn = 'where';

		if ($connective == 'or')
		{
			$fn = 'or_where';
		}

		switch ($operator)
		{
			case 'NOT IN':
				$fn .= '_not';
			case 'IN':
				$fn .= '_in';
			case '==':
				$operator = '';
		}

		if (is_null($value) || (is_string($value) && strtoupper($value) == 'NULL'))
		{
			switch ($operator)
			{
				case '!=':
					$operator = 'IS NOT';
					break;
				case '==':
				case '':
					$operator = 'IS';
					break;
			}

			$query->$fn("{$property} {$operator} NULL");
		}
		else
		{
			$query->$fn("{$property} {$operator}", $value);
		}
	}

	/**
	 * Add any search filters to the query.
	 *
	 * Searches are always applied as an AND at the end of the query, with
	 * OR's between all fields and ANDs for each set of words.
	 *
	 * So a search for 'fluffy cow' becomes:
	 * (title ~= fluffy AND title ~= cow) OR (body ~= fluffy AND body ~= cow)
	 *
	 * @param Query $query Query object
	 * @param Array $search Search data [field => [word => include?]]
	 */
	protected function applySearch($query, $search)
	{
		foreach ($search as $field => $words)
		{
			$field = $this->translateProperty($field);

			$query->or_start_like_group();

			foreach ($words as $word => $include)
			{
				$fn = $include ? 'like' : 'not_like';
				$query->$fn($field, $word);
			}

			$query->end_like_group();
		}
	}

	/**
	 *
	 */
	protected function translateProperty($property)
	{
		if (strpos($property, '.') === FALSE)
		{
			$alias = $this->root_alias;

			if ($property == $alias)
			{
				$model = $this->expandAlias($alias);
				$meta = $this->store->getMetaDataReader($model);

				$property = $meta->getPrimaryKey();
			}
		}
		else
		{
			list($alias, $property) = explode('.', $property);
			$alias = str_replace(':', '_m_', $alias);
		}

		if ( ! isset($this->model_fields[$alias]["{$alias}__{$property}"]))
		{
			throw new \Exception("Unknown field {$alias}.{$property}");
		}

		return $this->model_fields[$alias]["{$alias}__{$property}"];
	}

	/**
	 *
	 */
	protected function processWiths($query, $from, $from_alias)
	{
		$withs = $this->builder->getWiths();
		$this->recurseWiths($query, $from, $from_alias, $withs);
	}

	/**
	 *
	 */
	protected function recurseWiths($query, $parent, $parent_alias, $withs)
	{
		foreach ($withs as $child => $grandkids)
		{
			list($child, $child_alias) = $this->splitAlias($child);

			$relation = $this->store->getRelation($parent, $child);

			$child_model = $relation->getTargetModel();

			$queued_joins = $this->selectModel($query, $child_model, $child_alias, TRUE);

			$relation->modifyEagerQuery($query, $parent_alias, $child_alias);

			foreach ($queued_joins as $join)
			{
				call_user_func_array(array($query, 'join'), $join);
			}

			$this->storeRelation($parent_alias, $child_alias, $relation);

			if (count($grandkids))
			{
				$this->recurseWiths($query, $child_model, $child_alias, $grandkids);
			}
		}
	}

	/**
	 *
	 */
	protected function storeRelation($from_alias, $to_alias, $relation)
	{
		if ( ! isset($this->relations[$to_alias]))
		{
			$this->relations[$to_alias] = array();
		}

		$this->relations[$to_alias][$from_alias] = $relation;
	}

	/**
	 * Split up an alias.
	 */
	protected function splitAlias($string)
	{
		$string = trim($string);
		$parts = preg_split('/\s+AS\s+/i', $string);

		if ( ! isset($parts[1]))
		{
			return array($string, str_replace(':', '_m_', $string));
		}

		return $parts;
	}

	/**
	 *
	 */
	protected function storeAlias($alias, $model)
	{
		if (array_key_exists($alias, $this->aliases))
		{
			throw new \Exception("Not unique alias '{$alias}'.");
		}

		$this->aliases[$alias] = $model;
	}

	/**
	 *
	 */
	protected function expandAlias($alias)
	{
		return $this->aliases[$alias];
	}
}

// EOF
