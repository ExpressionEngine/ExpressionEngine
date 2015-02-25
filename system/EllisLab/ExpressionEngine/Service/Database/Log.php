<?php

namespace EllisLab\ExpressionEngine\Service\Database;

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
 * ExpressionEngine Database Log
 *
 * @package		ExpressionEngine
 * @subpackage	Database\Connection
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Log {

	/**
	 * @var Int Query count
	 */
	protected $count = 0;

	/**
	 * @var Array [Query => time]
	 */
	protected $queries = array();

	/**
	 * @var Bool Store queries for debugging?
	 */
	protected $save_queries = FALSE;

	/**
	 * Turn on/off saving queries
	 */
	public function saveQueries($save = TRUE)
	{
		$this->save_queries = $save;
	}

	/**
	 * Add a query to the log
	 */
	public function addQuery($sql, $time)
	{
		$this->count++;

		if ($this->save_queries)
		{
			$this->queries[] = array($sql, $time);
		}
	}

	/**
	 * Get all logged queries and their execution times
	 */
	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * Get the total query count
	 */
	public function getQueryCount()
	{
		return $this->count;
	}
}
