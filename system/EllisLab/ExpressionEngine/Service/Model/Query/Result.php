<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Collection;

class Result {

	protected $builder;
	protected $frontend;

	protected $db_result;

	protected $columns = array();
	protected $aliases = array();
	protected $objects = array();
	protected $relations = array();

	public function __construct(Builder $builder, $db_result, $aliases, $relations)
	{
		$this->builder = $builder;
		$this->db_result = $db_result;
		$this->aliases = $aliases;
		$this->relations = array_reverse($relations);
	}

	public function first()
	{
		$this->collectColumnsByAliasPrefix($this->db_result[0]);
		$this->initializeResultArray();
		$this->parseRow($row);

		$root = $this->builder->getFrom();
		return $this->objects[$root][0];
	}

	public function all()
	{
		if ( ! count($this->db_result))
		{
			return NULL;
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
		return new Collection($this->objects[$root]);
	}

	/**
	 *
	 */
	protected function parseRow($row)
	{
		foreach ($this->columns as $alias => $columns)
		{
			$model_data = array();

			foreach ($columns as $property)
			{
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

			$object = $this->frontend->make($name);
			$object->fill($model_data);

			$this->objects[$alias][$object->getId()] = $object;
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
				$this->matchRelation($parents, $kids, $relation);
			}
		}
	}

	/**
	 *
	 */
	protected function matchRelation($parents, $kids, $relation)
	{
		list($from_key, $to_key) = $relation->getKeys();

		foreach ($parents as $p_id => $parent)
		{
			$from_value = $parent->$from_key;
			$related_kids = array();

			foreach ($kids as $id => $kid)
			{
				$to_value = $kid->$to_key;

				if ($from_value == $to_value)
				{
					$related_kids[] = $kid;
					unset($kids[$id]);
				}
			}

			$name = $relation->getName();
			$parent->{'fill'.$name}($related_kids);
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

	public function setFrontend($frontend)
	{
		$this->frontend = $frontend;
		return $this;
	}
}