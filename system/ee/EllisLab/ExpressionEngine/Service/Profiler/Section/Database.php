<?php

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;
use EllisLab\ExpressionEngine\Service\View\View;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Database extends ProfilerSection {

	/**
	 * @var SQL Keywords we want highlighted
	 */
	protected $keywords = array(
		'SELECT',
		'DISTINCT',
		'FROM',
		'WHERE',
		'AND',
		'LEFT&nbsp;JOIN',
		'ORDER&nbsp;BY',
		'GROUP&nbsp;BY',
		'LIMIT',
		'INSERT',
		'INTO',
		'VALUES',
		'UPDATE',
		'OR&nbsp;',
		'HAVING',
		'OFFSET',
		'NOT&nbsp;IN',
		'IN',
		'LIKE',
		'NOT&nbsp;LIKE',
		'COUNT',
		'MAX',
		'MIN',
		'ON',
		'AS',
		'AVG',
		'SUM',
		'(',
		')'
	);

	/**
	 * Set the section's data
	 *
	 * @param  array  Array of Database $db objects
	 * @return void
	 **/
	public function setData($dbs)
	{
		$count = 0;

		foreach ($dbs as $db)
		{
			$count++;
			$log = $db->getLog();

			$label = $db->getConfig()->get('database');
			$this->data['duplicate_queries'][$label] = $this->getDuplicateQueries($log);

			$label .= '&nbsp;&nbsp;&nbsp;'.lang('profiler_queries').': '.$log->getQueryCount();
			$this->data['database'][$label] = $this->getQueries($log);
		}
	}

	/**
	 * Gets the view name needed to render the section
	 *
	 * @return string  the view/name
	 **/
	public function getViewName()
	{
		return 'profiler/database';
	}

	/**
	 * Build the data set for duplicate queries
	 *
	 * @param object	$log	a DB Log object
	 * @return array	duplicates [count, query]
	 **/
	private function getDuplicateQueries($log)
	{
		$duplicate_queries = array_filter($log->getQueryMetrics(),
			function($value)
			{
				return ($value['count'] > 1);
			}
		);

		$duplicates = array();
		foreach ($duplicate_queries as $dupe_query)
		{
			$query = $this->highlightSql($dupe_query['query'] . implode(' ', $dupe_query['locations']));

			$duplicates[] = array(
				'count' => $dupe_query['count'],
				'query' => $query
			);
		}

		return $duplicates;
	}

	/**
	 * Build the data set for queries
	 *
	 * @param object	$log	a DB Log object
	 * @return array	queries [time, query]
	 **/
	private function getQueries($log)
	{
		if ($log->getQueryCount() == 0)
		{
			return lang('profiler_no_queries');
		}

		foreach ($log->getQueries() as $query)
		{
			list($sql, $location, $time) = $query;

			$time = number_format($time, 4);
			$query = $this->highlightSql($sql.$location);

			$data[] = array(
				'time' => $time,
				'query' => $query
			);
		}

		return $data;
	}

	/**
	 * Syntax highlight the SQL
	 *
	 * @param string	$sql	the query and location
	 * @return string	syntax highlighted query
	 **/
	private function highlightSql($sql)
	{
		// Load the text helper so we can highlight the SQL
		ee()->load->helper('text');
		$highlighted = highlight_code($sql, ENT_QUOTES);

		foreach ($this->keywords as $keyword)
		{
			$highlighted = str_replace($keyword, '<b>'.$keyword.'</b>', $highlighted);
		}

		return $highlighted;
	}
}
