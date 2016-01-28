<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Collection;

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
 * ExpressionEngine Query Result
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Result {

	protected $builder;
	protected $facade;

	protected $db_result;

	protected $columns = array();
	protected $aliases = array();
	protected $objects = array();
	protected $relations = array();

	protected $related_ids = array();

	public function __construct(Builder $builder, $db_result, $aliases, $relations)
	{
		$this->builder = $builder;
		$this->db_result = $db_result;
		$this->aliases = $aliases;
		$this->relations = array_reverse($relations);
	}

	public function first()
	{
		$all = $this->all();

		if ( ! count($all))
		{
			return NULL;
		}

		return $all->first();
	}

	public function all()
	{
		if ( ! count($this->db_result))
		{
			return new Collection;
		}

		$this->collectColumnsByAliasPrefix($this->db_result[0]);
		$this->initializeResultArray();

		foreach ($this->db_result as $row)
		{
			$this->parseRow($row);
		}

		$this->constructRelationshipTree();

		reset($this->aliases);
		$root = key($this->aliases);

		foreach ($this->objects as $type => $objs)
		{
			foreach ($objs as $obj)
			{
				$obj->emit('afterLoad');
			}
		}

		return new Collection($this->objects[$root]);
	}

	/**
	 *
	 */
	protected function parseRow($row)
	{
		$by_row = array();

		foreach ($this->columns as $alias => $columns)
		{
			$model_data = array();

			foreach ($columns as $property)
			{
				if ( ! array_key_exists("{$alias}__{$property}", $row))
				{
					throw new \Exception("Unknown model property in query result: `{$alias}.{$property}`");
				}

				$value = $row["{$alias}__{$property}"];

				if (isset($value))
				{
					$model_data[$property] = $value;
				}
			}

			if (empty($model_data))
			{
				continue;
			}

			$name = $this->aliases[$alias];

			$object = $this->facade->make($name);
			$object->emit('beforeLoad'); // do not add 'afterLoad' to this method, it must happen *after* relationships are matched
			$object->fill($model_data);

			$this->objects[$alias][$object->getId()] = $object;

			$by_row[$alias] = $object->getId();
		}

		foreach ($by_row as $alias => $id)
		{
			$related = $by_row;
			unset($related[$alias]);

			if ( ! isset($this->related_ids[$alias]))
			{
				$this->related_ids[$alias] = array();
			}

			if ( ! isset($this->related_ids[$alias][$id]))
			{
				$this->related_ids[$alias][$id] = array();
			}

			$this->related_ids[$alias][$id][] = $related;
		}
	}

	/**
	 *
	 */
	protected function constructRelationshipTree()
	{
		foreach ($this->relations as $to_alias => $lookup)
		{
			$kids = $this->objects[$to_alias];

			foreach ($lookup as $from_alias => $relation)
			{
				$parents = $this->objects[$from_alias];

				$related_ids = $this->matchIds($parents, $from_alias, $to_alias);

				$this->matchRelation($parents, $kids, $related_ids, $relation);
			}
		}
	}

	/**
	 *
	 */
	protected function matchIds($parents, $from_alias, $to_alias)
	{
		$related_ids = array();

		foreach ($parents as $p_id => $parent)
		{
			$related_ids[$p_id] = array();

			$all_related = $this->related_ids[$from_alias][$p_id];

			foreach ($all_related as $potential)
			{
				if (isset($potential[$to_alias]))
				{
					$related_ids[$p_id][] = $potential[$to_alias];
				}
			}
		}

		return $related_ids;
	}

	/**
	 *
	 */
	protected function matchRelation($parents, $kids, $related_ids, $relation)
	{
		foreach ($parents as $p_id => $parent)
		{
			$set = array_unique($related_ids[$p_id]);
			$collection = array();

			foreach ($set as $id)
			{
				$collection[] = $kids[$id];
			}

			$name = $relation->getName();
			$parent->getAssociation($name)->fill($collection);
		}
	}

	/**
	 * Group all columns by their alias prefix.
	 */
	protected function collectColumnsByAliasPrefix($row)
	{
		$columns = array();

		foreach (array_keys($row) as $column)
		{
			list($alias, $property) = explode('__', $column);

			if ( ! array_key_exists($alias, $columns))
			{
				$columns[$alias] = array();
			}

			$columns[$alias][] = $property;
		}

		$this->columns = $columns;
	}

	/**
	 * Set up an array to hold all of our temporary data.
	 */
	protected function initializeResultArray()
	{
		foreach ($this->aliases as $alias => $model)
		{
			$this->objects[$alias] = array();
		}
	}

	public function setFacade($facade)
	{
		$this->facade = $facade;
		return $this;
	}
}

// EOF
