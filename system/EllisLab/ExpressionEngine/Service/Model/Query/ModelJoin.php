<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Query\Reference;

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
 * ExpressionEngine Model Join Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ModelJoin {

	private $from;
	private $to;
	private $from_key;
	private $to_key;
	private $operator;
	private $type;
	private $id;
	private $parent_id;

	/**
	 * @param $strategy Strategy instance
	 * @param $new_model To model alias
	 */
	public function __construct($strategy, $new_model)
	{
		$this->strategy = $strategy;
		$this->to = $new_model;
	}

	/**
	 * @param $known_table From model alias
	 */
	public function on($known_model)
	{
		$this->from = $known_model;
		return $this;
	}

	/**
	 * @param $from from key
	 * @param $to to key
	 * @param $operator comparison operator
	 */
	public function where($from, $operator, $to = NULL)
	{
		if ( ! isset($to))
		{
			$to = $operator;
			$operator = '=';
		}

		$this->from_key = $from;
		$this->to_key = $to;
		$this->operator = $operator;
		return $this;
	}

	/**
	 *
	 */
	public function type($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 *
	 */
	public function resolveWith($db)
	{
		$to = $this->strategy->findTable($this->to, $this->to_key);
		$to_alias = $this->strategy->rewriteTable($this->to, $this->to_key);
		$from_alias = $this->strategy->rewriteTable($this->from, $this->from_key);

		$db->join(
			"{$to} AS {$to_alias}",
			"{$from_alias}.{$this->from_key} {$this->operator} {$to_alias}.{$this->to_key}",
			$this->type
		);

		$additional_joins = $this->strategy->joinModelGateways($this->to, $to);

		foreach ($additional_joins as $join)
		{
			$join->type('LEFT OUTER');
			$join->resolveWith($db);
		}
	}
}
