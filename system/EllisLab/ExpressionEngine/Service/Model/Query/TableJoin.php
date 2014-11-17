<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

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
 * ExpressionEngine Model Table Join Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TableJoin {

	private $from;
	private $alias;
	private $to;
	private $from_key;
	private $to_key;
	private $operator;
	private $type;

	/**
	 * @param $translator Translator instance
	 * @param $new_table  To table name
	 */
	public function __construct($new_table, $alias = NULL)
	{
		$this->to = $new_table;
		$this->alias = $alias ?: $new_table;
	}

	/**
	 * @param $known_table From table
	 */
	public function on($known_table)
	{
		$this->from = $known_table;
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

	public function type($type)
	{
		$this->type = $type;
		return $this;
	}

	public function resolveWith($db)
	{
		$db->join(
			"{$this->to} AS {$this->alias}",
			"{$this->from}.{$this->from_key} {$this->operator} {$this->alias}.{$this->to_key}",
			$this->type
		);
	}
}