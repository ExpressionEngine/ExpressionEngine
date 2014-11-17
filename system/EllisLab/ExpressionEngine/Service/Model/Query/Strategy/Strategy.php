<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query\Strategy;

use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Factory;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder;

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
 * ExpressionEngine Model Query Execution Strategy Base Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Strategy {

	protected $db;
	protected $graph;
	protected $builder;
	protected $factory;

	public function __construct(Builder $builder, Factory $factory, $graph)
	{
		$this->builder = $builder;
		$this->factory = $factory;
		$this->graph = $graph;
	}

	public function setDb($db)
	{
		$this->db = $db;
	}

	/**
	 *
	 */
	abstract public function run();

	/**
	 *
	 */
	public function applyFilters($filters)
	{
		foreach ($filters as $filter_data)
		{
			// it's nested!
			if (count($filter_data) == 2)
			{
				list($prefix, $nested) = $filter_data;

				if ($prefix == 'and')
				{
					$this->db->start_group();
				}
				else
				{
					$this->db->or_start_group();
				}

				$this->applyFilters($nested, $this->db);
				$this->db->end_group();
			}
			else
			{
				$this->applyFilter(
					$filter_data[0],
					$filter_data[1],
					$filter_data[2],
					$filter_data[3],
					$this->db
				);
			}
		}
	}

	/**
	 *
	 */
	public function applyFilter($property, $operator, $value, $or = FALSE)
	{
		if ($operator == '==')
		{
			$operator = ''; // CI's query builder defaults to equals
		}

		if ($or)
		{
			if (strtolower($operator) == 'in')
			{
				$this->db->or_where_in($property, (array) $value);
			}
			elseif (strtolower($operator) == 'not in')
			{
				$this->db->or_where_not_in($property, (array) $value);
			}
			else
			{
				$this->db->or_where($property.' '.$operator, $value);
			}
		}
		else
		{
			if (strtolower($operator) == 'in')
			{
				$this->db->where_in($property, (array) $value);
			}
			elseif (strtolower($operator) == 'not in')
			{
				$this->db->where_not_in($property, (array) $value);
			}
			else
			{
				$this->db->where($property.' '.$operator, $value);
			}
		}
	}

	// Get the parent ids for quick batching
	// queries on all the little children.
	// @todo TODO batch this as well? if no filters?
	//
	// @todo This approach requires that the primary key be a single
	// primary key. Currently some tables (member groups, when used with msm)
	// use multi-key primaries. Which results in all sites getting cleared.
	// A single primary key will also optimize better on innodb, so we should
	// find a way to fix that.
	/**
	 *
	 */
	public function getMainIds()
	{
		$root_object = $this->builder->getModelObject();
		$primary_key = $this->getPrimaryKey($this->builder->getRootAlias());

		if (isset($root_object))
		{
			if ($root_object instanceof Collection)
			{
				return $root_object->pluck($primary_key);
			}

			return array($root_object->$primary_key);
		}


		$model = $this->builder->getRootModel();

		// We need to do a slightly modified query on the data we already have,
		// but **don't** ever modify the original query builder!!
		$builder = clone $this->builder;
		return $builder
			->fields("{$model}.{$primary_key}")
			->all()
			->pluck($primary_key);
	}

	/**
	 *
	 */
	public function getPrimaryKey($alias_name)
	{
		$ref = $this->builder->getAliasReference($alias_name);

		return $this->factory->getMetaData($ref->model, 'primary_key');
	}

	/**
	 *
	 */
	public function getFields($alias_name)
	{
		$ref = $this->builder->getAliasReference($alias_name);

		return $this->getModelFields($ref->model);
	}

	/**
	 *
	 */
	public function getModelFields($model)
	{
		if ( ! isset($this->tables[$model]))
		{
			$table_fields = array();

			$gateways = $this->factory->getMetaData($model, 'gateway_names');

			foreach ($gateways as $name)
			{
				$table_name = $this->factory->getMetaData($name, 'table_name');
				$field_list = $this->factory->getMetaData($name, 'field_list');

				$table_fields[$table_name] = $field_list;
			}

			$this->tables[$model] = $table_fields;
		}

		return $this->tables[$model];
	}
}