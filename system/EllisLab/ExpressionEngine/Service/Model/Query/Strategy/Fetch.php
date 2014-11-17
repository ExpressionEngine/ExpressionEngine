<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query\Strategy;

use EllisLab\ExpressionEngine\Service\Model\Query\ModelJoin;
use EllisLab\ExpressionEngine\Service\Model\Query\TableJoin;
use EllisLab\ExpressionEngine\Service\Model\Query\Result;

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
 * ExpressionEngine Model Query Fetch Strategy Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Fetch extends Strategy {

	protected $from;
	protected $joins = array();

	// todo THIS IS CRAZYNESS
	public function getBuilder()
	{
		return $this->builder;
	}

	/**
	 *
	 */
	public function run()
	{
		$this->buildQuery();

		$result_rows = $this->db->get()->result_array();

		// Hydrate the result models
		return new Result($this->factory, $this->builder, $result_rows);
	}

	/**
	 * Split out from `run()` for use in our count strategy
	 */
	protected function buildQuery()
	{
		// must do this before from as lazy queries modify from,
		// but CI's silly query builder requires that from() be
		// called before joins are created.
		$this->buildJoins();

		$this->db->from($this->getFrom());

		foreach ($this->joins as $join)
		{
			$join->resolveWith($this->db);
		}

		foreach ($this->getSelects() as $select)
		{
			$this->db->select($select, FALSE);
		}

		$this->applyFilters($this->getFilters());

		foreach ($this->getOrders() as $order)
		{
			list($property, $direction) = $order;
			$this->db->order_by($property, $direction);
		}

		$this->db->limit($this->getLimit(), $this->getOffset());
	}

	/**
	 * Retrieve the root table with its correct aliasing
	 */
	public function getFrom()
	{
		$alias = $this->from ?: $this->builder->getRootAlias();
		$table = key($this->getFields($alias));

		return "{$table} AS {$alias}_{$table}";
	}

	/**
	 *
	 */
	public function setFrom($alias)
	{
		$this->from = $alias;
	}

	/**
	 * Retrieve all filters with table.property keys
	 */
	public function getFilters()
	{
		return $this->rewriteFilters($this->builder->getFilters());
	}

	/**
	 * Retrieve all selected database fields, with aliases
	 * as required by the result parser.
	 */
	public function getSelects()
	{
		$selects = array();

		foreach ($this->builder->getReferences() as $alias => $ref)
		{
			// instance exists, no need to select it
			if (isset($ref->instance))
			{
				continue;
			}

			foreach ($this->getFields($alias) as $table => $fields)
			{
				foreach ($fields as $field)
				{
					$selects[] = "{$alias}_{$table}.{$field} AS {$alias}__{$field}";
				}
			}
		}

		return $selects;
	}

	/**
	 * Get all prepared joins
	 */
	public function buildJoins()
	{
		$this->joinModelGateways($this->builder->getRootAlias());

		foreach ($this->builder->getReferences() as $ref)
		{
			if (isset($ref->parent))
			{
				$ref->connectWithQuery($this);
			}
		}
	}

	/**
	 * Get all orders as DB fields
	 */
	public function getOrders()
	{
		$orders = array();

		foreach ($this->builder->getOrders() as $order)
		{
			$order[0] = $this->rewriteProperty($order[0]);
			$orders[] = $order;
		}

		return $orders;
	}

	/**
	 * Get the requested limit
	 */
	public function getLimit()
	{
		return $this->builder->getLimit();
	}

	/**
	 * Get the requested offset
	 */
	public function getOffset()
	{
		return $this->builder->getOffset();
	}

	// Utilties

	/**
	 * Prepare a join on another model. Used by the graph edges to
	 * create the correct join.
	 */
	public function joinModel($new_model)
	{
		$join = new ModelJoin($this, $new_model);
		$this->joins[] = $join;

		return $join;
	}

	/**
	 * Prepare a table join
	 */
	public function joinTable($new_table, $alias = NULL)
	{
		$join = new TableJoin($new_table, $alias);

		$this->joins[] = $join;

		return $join;
	}

	// Rewriters

	/**
	 * Rewrite `filter(Model.field)` to `filter(table_alias.field)`
	 */
	public function rewriteFilters($filters)
	{
		foreach ($filters as &$filter_data)
		{
			if (count($filter_data) == 2)
			{
				$filter_data[1] = $this->rewriteFilters($filter_data[1]);
			}
			else
			{
				$filter_data[0] = $this->rewriteProperty($filter_data[0]);
			}
		}

		return $filters;
	}

	/**
	 * Rewrite `Model.property` to `table_alias.property`
	 */
	public function rewriteProperty($selector)
	{
		if ( ! strpos($selector, '.'))
		{
			$selector = $this->builder->getRootAlias().'.'.$selector;
		}

		list($alias, $field) = explode('.', $selector);

		$table = $this->rewriteTable($alias, $field);

		return "{$table}.{$field}";
	}

	/**
	 * Get an *aliased* table name for a field given a model alias
	 * and field name. Use `findTable()` if you need the raw name.
	 */
	public function rewriteTable($alias, $field)
	{
		// todo nastyness for many-to-many
		if ($alias == 'PIVOT')
		{
			return 'PIVOT';
		}

		$table = $this->findTable($alias, $field);

		return "{$alias}_{$table}";
	}

	/**
	 * Get a table name given an alias and a field
	 */
	public function findTable($alias, $field)
	{
		$ref = $this->builder->getAliasReference($alias);

		return $this->findModelTable($ref->model, $field);
	}

	/**
	 *
	 */
	public function findModelTable($model, $field)
	{
		foreach ($this->getModelFields($model) as $table => $fields)
		{
			if (in_array($field, $fields))
			{
				return $table;
			}
		}

		return NULL;
	}


	/**
	 * Given an alias and already selected table, get join statements
	 * connecting all other tables on that model to the selected one.
	 */
	public function joinModelGateways($alias, $known_table = NULL)
	{
		$pk = $this->getPrimaryKey($alias);

		$table_names = array_keys($this->getFields($alias));
		$known_table = $known_table ?: current($table_names);

		foreach ($table_names as $add_table)
		{
			if ($add_table != $known_table)
			{
				$this
					->joinTable($add_table, "{$alias}_{$add_table}")
					->on("{$alias}_{$known_table}")
					->where($pk, '=', $pk);
			}
		}
	}
}