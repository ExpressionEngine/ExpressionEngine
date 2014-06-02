<?php
namespace EllisLab\ExpressionEngine\Library\CP;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Pagination Class
 *
 * @package		ExpressionEngine
 * @subpackage	Library
 * @category	CP
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Pagination {
	private $current_page;
	private $first = 1;
	private $prev;
	private $pages;
	private $next;
	private $last;

	/**
	 * Calculates pages based on number per page and total. This will also
	 * provide the page location of current, firss, and last pages as well as
	 * previous and next when available.
	 *
	 * @param int $per_page		The number of items per pages
	 * @param int $total_count	The total number of itmes being paginated
	 * @param int $current_page	The current page (defaults to 1)
	 */
	public function __construct($per_page, $total_count, $current_page = 1)
	{
		$this->current_page = $current_page;
		$this->prev         = ($current_page - 1 >= 1) ? ($current_page - 1) : NULL;
		$this->pages        = (int) ceil($total_count / $per_page);
		$this->next         = ($current_page + 1 <= $this->pages) ? ($current_page + 1) : NULL;
		$this->last         = $this->pages;
	}

	/**
	 * This allows us to do Pagination::create(...)->cp_links(...)
	 */
	public static function create($per_page, $total_count, $current_page = 1)
	{
		return new static($per_page, $total_count, $current_page);
	}

	/**
	 * Creates an array of URLs
	 *
	 * @param  object	$base_url		A CP\URL object
	 * @param  int		$pages			The number of numbered pages to calculate
	 * @param  string	$page_variable	The name of the page variable in the query string
	 * @return array	Returns an associative array of URLs
	 *   e.g. 'current_page' => 2,
	 *        'first' => 'http://ee3/admin.php?/cp/logs/cp',
	 *        'prev'  => 'http://ee3/admin.php?/cp/logs/cp?page=1',
	 *        'next'  => 'http://ee3/admin.php?/cp/logs/cp?page=3',
	 *        'last'  => 'http://ee3/admin.php?/cp/logs/cp?page=4',
	 *        'pages' =>
	 *            '1'  => 'http://ee3/admin.php?/cp/logs/cp?page=1',
	 *            '2'  => 'http://ee3/admin.php?/cp/logs/cp?page=2',
	 *            '3'  => 'http://ee3/admin.php?/cp/logs/cp?page=3',
	 */
	public function cp_links($base_url, $pages = 3, $page_variable = 'page')
	{
		$pages--; // Remove the current page from the count.

		$links['current_page'] = $this->current_page;
		$links['first'] = $base_url->compile();
		foreach (array('prev', 'next', 'last') as $key)
		{
			if ($this->{$key} === NULL) continue;

			$url = clone $base_url;
			$url->setQueryStringVariable($page_variable, $this->{$key});
			$links[$key] = $url->compile();
		}

		$start = ($this->current_page - 1 > 1) ? $this->current_page - 1 : 1;
		if ($start + $pages <= $this->pages)
		{
			$end = $start + $pages;
		}
		else
		{
			$end = $pagination['pages'];
			if ($end - $pages > 1)
			{
				$start = $end - $pages;
			}
			else
			{
				$start = 1;
			}
		}

		for ($i = $start; $i <= $end; $i++)
		{
			$url = clone $base_url;
			$url->setQueryStringVariable($page_variable, $i - 1);
			$links['pages'][$i] = $url->compile();
		}

		return $links;
	}
}

// END CLASS

/* End of file Pagination.php */
/* Location: ./system/EllisLab/ExpressionEngine/Library/Pagination/Pagination.php */