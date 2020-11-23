<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use LogicException;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\Content\ContentModel;

/**
 * Select Query
 */
class Select extends Query {

	protected $root_alias = '';
	protected $aliases = array();
	protected $relations = array();
	protected $model_fields = array();
	protected $searched_fields = array();
	protected $additional_search = array();

	/**
	 * @var int $table_join_limit MySQL only allows 61 tables in a single
	 *   statement. We'll keep our joins nunder that.
	 */
	private $table_join_limit = 59;

	protected function getClass($alias = '')
	{
		$alias = ($alias) ?: $this->root_alias;
		$model = $this->expandAlias($alias);
		$meta = $this->store->getMetaDataReader($model);
		return $meta->getClass();
	}

	/**
	 *
	 */
	public function run()
	{
		$query = $this->buildQuery();

		$result_array = $query->get()->result_array();

		if ( ! empty($result_array))
		{
			$withs = $this->builder->getWiths();
			$aliases = array_merge(array($this->root_alias), array_keys($withs));
			foreach ($aliases as $alias)
			{
				list($from, $alias) = $this->splitAlias($alias);

				$class = $this->getClass($alias);
				if ( ! is_null($class::getMetaData('field_data')))
				{
					$result_array = $this->getExtraData($alias, $result_array);
				}
			}
		}

		$result = new Result(
			$result_array,
			$this->aliases,
			$this->relations
		);

		return $result;
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

		$class = $this->getClass();

		if ( ! is_null($class::getMetaData('field_data')))
		{
			$this->augmentQuery($query);
		}

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
			$this->applySearch($query, $search);
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

		// Gather columns needed to fulfill relationships from this model
		$relation_keys = array_map(function($relation) use ($alias)
		{
			$keys = $relation->getKeys();
			return array_shift($keys);
		}, $this->store->getAllRelations($model));
		$relation_keys = array_unique(array_values($relation_keys));

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
				// or they specifically chose this one to be selected,
				// or the column is needed to fulfill a relationship
				if (empty($fields) OR
					in_array("{$alias}.{$column}", $fields) OR
					in_array("{$alias}.*", $fields) OR
					in_array($column, $relation_keys))
				{
					$query->select("{$table_alias}.{$column} as {$alias}__{$column}", FALSE);
				}
			}
		}

		return $queued_joins;
	}

	protected function getExtraData($alias, $result_array)
	{
		$meta  = $this->store->getMetaDataReader($this->expandAlias($alias));
		$class = $meta->getClass();

		$fields = $this->getFields();

		// Bail if this query is selecting specific fields and none of those
		// fields would be found in the field data tables
		if ( ! empty($fields))
		{
			$found = FALSE;
			foreach ($fields as $field)
			{
				if (strpos($field, 'field_id_') !== FALSE)
				{
					$found = TRUE;
				}
			}

			if ( ! $found)
			{
				return $result_array;
			}
		}

		$meta_field_data = $class::getMetaData('field_data');

		$field_model     = ee('Model')->make($meta_field_data['field_model']);

		// let's make life a bit easier
		$item_key_column   = $alias . '__' . $meta->getPrimaryKey();
		$table_prefix      = $alias;
		$join_table_prefix = $field_model->getTableName();
		$column_prefix     = $field_model->getColumnPrefix();
		$primary_key       = $meta->getPrimaryKey();
		$table_name        = $class::getMetaData('table_name');
		$parent_key        = "{$table_name}.{$primary_key}";

		if (array_key_exists('group_column', $meta_field_data))
		{
			$meta_field_data['group_column'] = $alias . '__' . $meta_field_data['group_column'];
			$structure_ids = array_map(function($column) use($meta_field_data){
				if (array_key_exists($meta_field_data['group_column'], $column))
				{
					return $column[$meta_field_data['group_column']];
				}
			}, $result_array);

			$structure_ids = array_unique($structure_ids);
			$structure_models = ee('Model')->get($meta_field_data['structure_model'], $structure_ids)->all();

			$fields = array();
			foreach ($structure_models as $model)
			{
				foreach ($model->getAllCustomFields() as $f)
				{
					if ( ! $f->legacy_field_data)
					{
						$fields[$f->field_id] = $f;
					}
				}
			}
			$fields = array_values($fields);
		}
		else
		{
			if ($meta_field_data['field_model'] == 'MemberField' && ! empty(ee()->session))
			{
				$fields = ee()->session->cache('EllisLab::MemberGroupModel', 'getCustomFields');

				// might be empty, so need to be specific
				if ( ! is_array($fields))
				{
					// get ALL fields, since this cache key is shared elsewhere and expects all fields
					$fields = ee('Model')->get($meta_field_data['field_model'])
						->all()
						->asArray();
					ee()->session->set_cache('EllisLab::MemberGroupModel', 'getCustomFields', $fields);
				}

				// filter just for non-legacy fields
				$fields = new Collection($fields);
				$fields = $fields->filter(function($mfield) use ($column_prefix)
					{
						$colname = $column_prefix.'legacy_field_data';
						return $mfield->$colname == FALSE;
					})->asArray();
			}
			else
			{
				$fields = ee('Model')->get($meta_field_data['field_model'])
				->filter($column_prefix.'legacy_field_data', 'n')
				->all()
				->asArray();
			}
		}

		if ( ! empty($fields))
		{
			$entry_ids = array_map(function($column) use ($item_key_column) {
				return $column[$item_key_column];
			}, $result_array);

			$chunks = array_chunk($fields, $this->table_join_limit);

			foreach ($chunks as $fields)
			{
				$query = ee('Model/Datastore')->rawQuery();

				$main_table = "{$table_prefix}_field_id_{$fields[0]->field_id}";

				$query->from($table_name);
				$query->select("{$parent_key} as {$item_key_column}", FALSE);

				foreach ($fields as $field)
				{
					$field_id = $field->getId();

					$table_alias = "{$table_prefix}_field_id_{$field_id}";

					foreach ($field->getColumnNames() as $column)
					{
						$query->select("{$table_alias}.{$column} as {$table_prefix}__{$column}", FALSE);
					}

					$query->join("{$join_table_prefix}{$field_id} AS {$table_alias}", "{$table_alias}.{$primary_key} = {$parent_key}", 'LEFT');
				}

				$query->where_in("{$parent_key}", $entry_ids);

				$data = $query->get();

				foreach ($data->result_array() as $row)
				{
					array_walk($result_array, function (&$data, $key, $field_data) use ($item_key_column){
						if ($data[$item_key_column] == $field_data[$item_key_column])
						{
							$data = array_merge($data, $field_data);
						}
					}, $row);
				}
				$data->free_result();
			}
		}

		return $result_array;
	}

	private function getCustomFields($model_name, $column_prefix, $field_ids)
	{
		$cache_key = "CustomFields/{$model_name}/" . implode(',', $field_ids);

		if (($fields = ee()->session->cache(__CLASS__, $cache_key, FALSE)) === FALSE)
		{
			$fields = ee('Model')->get($model_name)
				->fields($column_prefix.'field_id')
				->filter($column_prefix.'field_id', 'IN', $field_ids)
				->filter($column_prefix.'legacy_field_data', 'n')
				->all();

			ee()->session->set_cache(__CLASS__, $cache_key, $fields);
		}

		return $fields;
	}

	protected function augmentQuery($query)
	{
		$meta  = $this->store->getMetaDataReader($this->expandAlias($this->root_alias));
		$class = $meta->getClass();

		$meta_field_data = $class::getMetaData('field_data');

		$field_model = ee('Model')->make($meta_field_data['field_model']);

		// let's make life a bit easier
		$table_prefix      = $meta->getName();
		$join_table_prefix = $field_model->getTableName();
		$column_prefix     = $field_model->getColumnPrefix();
		$primary_key       = $meta->getPrimaryKey();
		$parent_key        = "{$table_prefix}__{$primary_key}";

		$field_ids = array();

		// It's possible we'll be asked to search across more tables than we can
		// JOIN in a single query. In such cases we can simply search each of the
		// tables individually and build up a list of primary keys to filter on
		foreach ($this->builder->getSearch() as $field => $words)
		{
			if (strpos($field, $column_prefix.'field_id') === 0)
			{
				$field_ids[] = str_replace($column_prefix.'field_id_', '', $field);
			}
		}

		if ( ! empty($field_ids))
		{
			$cache_key = "Query/Select/additionalSearch/" . md5(json_encode($field_ids));

			if (($cached_search = ee()->session->cache(__CLASS__, $cache_key, FALSE)) === FALSE)
			{
				$pks = array();

				$fields = $this->getCustomFields($meta_field_data['field_model'], $column_prefix, $field_ids);

				// If we have fewer than our table limit we'll just keep on keeping on.
				if ($fields->count() > $this->table_join_limit)
				{
					$search = $this->builder->getSearch();

					foreach ($fields->pluck('field_id') as $field_id)
					{
						$field = "field_id_{$field_id}";
						$words = $search[$field];

						$sq = ee('Model/Datastore')->rawQuery();
						$sq->select($primary_key);
						$sq->from("{$join_table_prefix}{$field_id}");

						$sq->or_start_like_group();
						foreach ($words as $word => $include)
						{
							$fn = $include ? 'like' : 'not_like';
							$sq->$fn($field, $word);
						}
						$sq->end_like_group();

						$data = $sq->get();
						foreach ($data->result_array() as $row)
						{
							$pks[] = $row[$primary_key];
						}
						$data->free_result();

						$this->searched_fields[] = "{$column_prefix}field_id_{$field_id}";
					}

					if ( ! empty($pks))
					{
						$pks = array_unique($pks);
						$tables = array_keys($meta->getTables());
						$this->additional_search = array("{$table_prefix}_{$tables[0]}.{$primary_key}", $pks);
					}

					// Reset: we've investigated these and don't need to JOIN them for
					// searching's sake
					$field_ids = array();
				}

				ee()->session->set_cache(__CLASS__, $cache_key, array($this->additional_search, $this->searched_fields, $field_ids));
			}
			else
			{
				$this->additional_search = $cached_search[0];
				$this->searched_fields = $cached_search[1];
				$field_ids = $cached_search[2];
			}
		}

		$field_ids = array_merge(
			$field_ids,
			$this->getFieldIdsFromFilters($this->builder->getFilters(), $column_prefix)
		);

		foreach ($this->builder->getOrders() as $order)
		{
			$field = $order[0];

			if (strpos($field, $column_prefix.'field_id') === 0)
			{
				$field_ids[] = str_replace($column_prefix.'field_id_', '', $field);
			}
		}

		if ( ! empty($field_ids))
		{
			$field_ids = array_unique($field_ids);

			$fields = $this->getCustomFields($meta_field_data['field_model'], $column_prefix, $field_ids);

			foreach ($fields->pluck('field_id') as $field_id)
			{
				$table_alias = "{$table_prefix}_field_id_{$field_id}";
				$column_alias = "{$table_prefix}__{$column_prefix}field_id_{$field_id}";

				$query->select("{$table_alias}.{$column_prefix}field_id_{$field_id} as {$column_alias}", FALSE);
				$query->join("{$join_table_prefix}{$field_id} AS {$table_alias}", "{$table_alias}.{$primary_key} = {$this->model_fields[$table_prefix][$parent_key]}", 'LEFT');
				$this->model_fields[$table_prefix][$column_alias] = $table_alias . ".{$column_prefix}field_id_{$field_id}";
			}
		}
	}

	/**
	 * Given an array of filters from the Builder, extracts all the custom field
	 * IDs from them
	 *
	 * @param array $filters Result of getFilters() on Builder
	 * @param string $column_prefix Field model's content prefix
	 */
	protected function getFieldIdsFromFilters($filters, $column_prefix)
	{
		$field_ids = [];

		foreach ($filters as $filter)
		{
			// Nested filter group
			if (count($filter) == 2)
			{
				list($connective, $nested) = $filter;

				$field_ids = array_merge($field_ids, $this->getFieldIdsFromFilters($nested, $column_prefix));
			}
			else
			{
				$field = $filter[0];
				if (strpos($field, $column_prefix.'field_id') === 0)
				{
					$field_ids[] = str_replace($column_prefix.'field_id_', '', $field);
				}
			}
		}

		return $field_ids;
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

		$binary = $this->isBinaryComparison($property);
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

		$comparison = "{$property} {$operator}";

		if (is_null($value) || (is_string($value) && strtoupper($value) == 'NULL'))
		{
			// in case it was a string
			$value = NULL;

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

			$comparison = "{$property} {$operator} NULL";
		}

		if (in_array($fn, array('where_in', 'or_where_in')))
		{
			$query->$fn($comparison, $value, $binary);
			return;
		}

		$query->$fn($comparison, $value, TRUE, $binary);
	}

	/**
	 * Given a property name, which can be aliased, returns whether or not the
	 * property is set to be compared in a binary fashion, typically for
	 * case-sensitivity purposes
	 *
	 * @param string $property Property name, optionally aliased
	 * @param boolean
	 */
	protected function isBinaryComparison($property)
	{
		if (strpos($property, '.') !== FALSE)
		{
			list($alias, $property) = explode('.', $property);
			list($from, $alias) = $this->splitAlias($alias);
			$from = $this->expandAlias($alias);
		}
		else
		{
			$from = $this->builder->getFrom();
			list($from, $alias) = $this->splitAlias($from);
		}

		$meta = $this->store->getMetaDataReader($from);
		return in_array($property, $meta->getBinaryComparisons());
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
			if (in_array($field, $this->searched_fields))
			{
				unset($search[$field]);
				continue;
			}
		}

		if (!empty($search)) {
			$query->start_like_group();
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

		if ( ! empty($this->additional_search))
		{
			// We need to add this WHERE inside the LIKE group else the query doesn't work
			$fn = !empty($search) ? 'or_where_in' : 'where_in';
			$query->$fn($this->additional_search[0], $this->additional_search[1]);
			if (!empty($search)) {
				$where = array_pop($query->ar_where);
				$query->ar_like[] = $where;
			}
		}

		if (!empty($search)) {
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
