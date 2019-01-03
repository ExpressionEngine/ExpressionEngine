<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\PHPUnit\Extensions\NoopDatabase;

use Mockery as m;

/**
 * Mockable query builder that has some nice behaviors that make testing
 * easier:
 *
 * All of the shortcut functions call their most common constituent abstract
 * parts.
 *
 * So this:
 *
 * db->update('table', array('data' => 'value'), array('where' => 'something'))
 *
 * Will automatically call:
 *
 * db->where(array('where' => 'something'))
 * db->set(array('data' => 'value'))
 *
 * This way assertions can be written to support clean refactoring.
 *
 * Similarly, calls to infrequently used function such as set() are combined
 *
 */
class NoopQueryBuilder {

	public static function getMock(\PHPUnit_Framework_TestCase $test)
	{
		/*
		$r = new \ReflectionClass(get_called_class());

		$methods = array();

		foreach ($r->getMethods() as $m)
		{
			if ($m->name != '__call' && $m->name != 'getMock')
			{
				$methods[] = $m->name;
			}
		}

		return m::mock(get_called_class(), $methods);
		*/
		return m::mock('\CI_DB_active_record');
	}

	public function __call($method, $args)
	{
		// if insert() and arg 2 then call set() and insert() separately
		// etc ...
		switch ($method)
		{
			case 'get':
		}

		switch (count($args))
		{
			case 0: return $this->$method();
			case 1: return $this->$method($args[0]);
			case 2: return $this->$method($args[0], $args[1]);
			case 3: return $this->$method($args[0], $args[1], $args[2]);
			case 4: return $this->$method($args[0], $args[1], $args[2], $args[3]);
			default: throw new Exception('Too Many Arguments');
		}
	}
}

class NoopActiveRecord {

	protected function limit($value, $offset = '')
	{
		$this->offset($offset);
		return $this;
	}

	protected function get($table = '', $limit = null, $offset = null)
	{
		// $this->get($table) happened to get here
		$this->limit($limit);
		$this->offset($offset);
	}

	protected function get_where($table = '', $where = null, $limit = null, $offset = null)
	{
		$this->where($where);
		$this->limit($limit);
		$this->offset($offset);
		$this->get($table);
	}

	protected function insert_batch($table = '', $set = NULL)
	{
		if (is_array($set))
		{
			foreach ($set as $k => $v)
			{
				$this->set_insert_batch($k, $v);
			}
		}
	}

	protected function insert($table = '', $set = NULL)
	{
		$this->set($set);
	}

	protected function replace($table = '', $set = NULL)
	{
		$this->set($set);
	}

	protected function update($table = '', $set = NULL, $where = NULL, $limit = NULL)
	{
		$this->set($set);
		$this->where($where);
		$this->limit($limit);
	}

	protected function update_batch($table = '', $set = NULL, $index = NULL)
	{
		if (is_array($set))
		{
			$this->set_update_batch($set, $index);
		}
	}

	protected function delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE)
	{
		$this->where($where);
		$this->limit($limit);
	}

	/** Untouched Functions **/
	protected function set_insert_batch($key, $value = '', $escape = TRUE) { return $this; }
	protected function set_update_batch($key, $index = '', $escape = TRUE) { return $this; }

	protected function set($key, $value = '', $escape = TRUE) { return $this; }
	protected function select($select = '*', $escape = NULL) { return $this; }
	protected function select_max($select = '', $alias = '') { return $this; }
	protected function select_min($select = '', $alias = '') { return $this; }
	protected function select_avg($select = '', $alias = '') { return $this; }
	protected function select_sum($select = '', $alias = '') { return $this; }
	protected function join($table, $cond, $type = '') { return $this; }
	protected function where($key, $value = NULL, $escape = TRUE) { return $this->_where($key, $value, 'AND ', $escape); }
	protected function or_where($key, $value = NULL, $escape = TRUE) { return $this; }
	protected function where_in($key = NULL, $values = NULL) { return $this; }
	protected function or_where_in($key = NULL, $values = NULL) { return $this; }
	protected function where_not_in($key = NULL, $values = NULL) { return $this; }
	protected function or_where_not_in($key = NULL, $values = NULL) { return $this; }
	protected function _where_in($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ') { return $this; }
	protected function like($field, $match = '', $side = 'both') { return $this; }
	protected function not_like($field, $match = '', $side = 'both') { return $this; }
	protected function or_like($field, $match = '', $side = 'both') { return $this; }
	protected function or_not_like($field, $match = '', $side = 'both') { return $this; }
	protected function having($key, $value = '', $escape = TRUE) { return $this; }
	protected function or_having($key, $value = '', $escape = TRUE) { return $this; }
	protected function order_by($orderby, $direction = '', $escape = NULL) { return $this; }

	protected function truncate($table = '') {}
	protected function dbprefix($table = '') {}
	protected function empty_table($table = '') {}
	protected function count_all_results($table = '') {}
	protected function insert_id() {return 1;}

	protected function from($from) { return $this; }
	protected function offset($offset) { return $this; }
	protected function group_by($by) { return $this; }
	protected function distinct($val = TRUE) { return $this; }

	protected function start_cache() {}
	protected function stop_cache() {}
	protected function flush_cache() {}

	protected function _compile_select() {}
}
