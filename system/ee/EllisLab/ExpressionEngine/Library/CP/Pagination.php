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

	/**
	 * @var int $current_page The page number being displayed
	 */
	private $current_page;

	/**
	 * @var int $first The page number of the first page
	 */
	private $first = 1;

	/**
	 * @var int $prev The page number for the previous page
	 */
	private $prev;

	/**
	 * @var int $pages The total number of pages
	 */
	private $pages;

	/**
	 * @var int $next The page number for the next page
	 */
	private $next;

	/**
	 * @var int $last The page number of the last page
	 */
	private $last;

	/**
	 * @var int $total_count The total number of itmes being paginated
	 */
	private $total_count;

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
		foreach (array('per_page', 'total_count', 'current_page') as $param)
		{
			if ( ! is_numeric($$param))
			{
				throw new \InvalidArgumentException("The {$param} argument must be a number.");
			}
		}

		foreach (array('per_page', 'current_page') as $param)
		{
			if ($$param < 1)
			{
				throw new \InvalidArgumentException("The {$param} argument must be greater than 0. \"{$$param}\" was passed.");
			}
		}

		// Total count can be 0
		if ($total_count < 0)
		{
			throw new \InvalidArgumentException("The total_count argument must be greater than 0. \"{$total_count}\" was passed.");
		}

		// Cast any floats or numeric strings to integers
		$per_page = (int) $per_page;
		$this->total_count = (int) $total_count;
		$current_page = (int) $current_page;

		$this->current_page = $current_page;
		$this->prev         = ($current_page - 1 >= 1) ? ($current_page - 1) : NULL;
		$this->pages        = (int) ceil($this->total_count / $per_page);
		$this->next         = ($current_page + 1 <= $this->pages) ? ($current_page + 1) : NULL;
		$this->last         = $this->pages;
	}

	/**
	 * Creates an array of URLs
	 *
	 * @param  object	$base_url		A CP\URL object
	 * @param  int		$pages			The number of numbered pages to calculate
	 * @param  string	$page_variable	The name of the page variable in the query string
	 * @return array	Returns an associative array of URLs
	 *   e.g. 'total_count' => 123,
	 *        'current_page' => 2,
	 *        'first' => 'http://ee3/admin.php?/cp/logs/cp',
	 *        'prev'  => 'http://ee3/admin.php?/cp/logs/cp?page=1',
	 *        'next'  => 'http://ee3/admin.php?/cp/logs/cp?page=3',
	 *        'last'  => 'http://ee3/admin.php?/cp/logs/cp?page=4',
	 *        'pages' =>
	 *            '1'  => 'http://ee3/admin.php?/cp/logs/cp?page=1',
	 *            '2'  => 'http://ee3/admin.php?/cp/logs/cp?page=2',
	 *            '3'  => 'http://ee3/admin.php?/cp/logs/cp?page=3',
	 */
	public function cp_links(Url $base_url, $pages = 3, $page_variable = 'page')
	{
		// Show no pagination unless we have at least 2 pages
		if ($this->pages < 2)
		{
			return array();
		}

		// Check for exceptions (i.e. invalid arguments)
		if ( ! is_numeric($pages))
		{
			throw new \InvalidArgumentException('The pages argument must be a number.');
		}

		if ($pages < 1)
		{
			throw new \InvalidArgumentException("The pages argument must be greater than 0. \"{$pages}\" was passed.");
		}

		if (is_array($page_variable) || (is_object($page_variable) && ! method_exists($page_variable, '__toString')))
		{
			throw new \InvalidArgumentException('The page_variable argument must be a string.');
		}

		// Remove the current page from the count and force an integer instead of a float.
		$pages = (int) $pages - 1;

		$links['total_count'] = $this->total_count;
		$links['current_page'] = $this->current_page;
		$links['first'] = $base_url->compile();
		foreach (array('prev', 'next', 'last') as $key)
		{
			if ($this->{$key} === NULL) continue;

			$url = clone $base_url;
			$url->setQueryStringVariable((string) $page_variable, $this->{$key});
			$links[$key] = $url->compile();
		}

		$start = ($this->current_page - 1 > 1) ? $this->current_page - 1 : 1;
		if ($start + $pages <= $this->pages)
		{
			$end = $start + $pages;
		}
		else
		{
			$end = $this->pages;
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
			$url->setQueryStringVariable($page_variable, $i);
			$links['pages'][$i] = $url->compile();
		}

		return $links;
	}
}

// END CLASS

/* End of file Pagination.php */
/* Location: ./system/EllisLab/ExpressionEngine/Library/Pagination/Pagination.php */