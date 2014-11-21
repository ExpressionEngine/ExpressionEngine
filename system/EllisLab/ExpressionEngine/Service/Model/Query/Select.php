<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use LogicException;

class Select extends Query {

	protected $root_alias = '';
	protected $aliases = array();
	protected $relations = array();

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
		$this->applyFilters($query, $builder->getFilters());

		// orders
		$this->applyOrders($query, $builder->getOrders());

		$query->limit($builder->getLimit(), $builder->getOffset());

		return $query;
	}

	/**
	 *
	 */
	protected function selectModel($query, $model, $alias = NULL)
	{
		$alias = $alias ?: $model;
		$this->storeAlias($alias, $model);

		$meta = $this->store->getMetaDataReader($model);
		$fields = $this->builder->getFields();
		$tables = $meta->getTables();

		foreach ($tables as $table => $table_fields)
		{
			$table_alias = "{$alias}_{$table}";
			$query->from("{$table} as {$table_alias}");

			foreach ($table_fields as $column)
			{
				if (empty($fields) OR isset($fields[$column]))
				{
					$query->select("{$table_alias}.{$column} as {$alias}__{$column}");
				}
			}
		}
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

			$query->order($property, $direction);
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
				list($predicate, $nested) = $filter_data;

				if ($predicate == 'and')
				{
					$query->start_group();
				}
				elseif ($predicate == 'or')
				{
					$query->or_start_group();
				}
				else
				{
					throw new LogicException('Invalid filter group predicate.');
				}

				$this->applyFilters($query, $nested);
				ee()->db->end_group();
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
		list($property, $operator, $value, $predicate) = $filter;

		$property = $this->translateProperty($property);

		$fn = 'where';

		switch ($operator)
		{
			case 'NOT IN':
				$fn .= '_not';
			case 'IN':
				$fn .= '_in';
				break;
			case '==':
				$operator = '';
				break;
		}

		if ($predicate == 'or')
		{
			$fn = 'or_'.$fn;
		}

		$query->$fn("{$property} {$operator}", $value);
	}

	/**
	 *
	 */
	protected function translateProperty($property)
	{
		if (strpos($property, '.') === FALSE)
		{
			$property = $this->root_alias.'.'.$property;
		}

		return str_replace('.', '__', $property);
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

			$this->selectModel($query, $child_model, $child_alias);

			$relation->modifyEagerQuery($query, $parent_alias, $child_alias);

			$this->storeRelation($parent_alias, $child_alias, $relation);

			if (count($grandkids))
			{
				$this->recurseWiths($query, $child, $child_alias, $grandkids);
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
			return array($string, $string);
		}

		return $parts;
	}

	/**
	 *
	 */
	protected function storeAlias($alias, $model)
	{
		$this->aliases[$alias] = $model;
	}
}