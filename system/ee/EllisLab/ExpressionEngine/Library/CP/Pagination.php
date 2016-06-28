<?php
namespace EllisLab\ExpressionEngine\Library\CP;

use EllisLab\ExpressionEngine\Service\View\View;

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
 * ExpressionEngine Pagination Class
 *
 * @package		ExpressionEngine
 * @subpackage	Library
 * @category	CP
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Pagination {

	/**
	 * @var int $per_page The number of items per page
	 */
	private $per_page = 25;

	/**
	 * @var int $current_page The page number being displayed
	 */
	private $current_page = 1;

	/**
	 * @var int $total_count The total number of itmes being paginated
	 */
	private $total_count;

	/**
	 * @var string $page_variable The query string variable name
	 */
	private $page_variable = 'page';

	/**
	 * @var int $pages_to_display The number of numbered pages to calculate
	 */
	private $pages_to_display = 3;

	/**
	 * @var View $view A View object for rendering this alert
	 */
	private $view;

	/**
	 * Constructor: sets the total number of items to be paginated and injects
	 * a View object for rendering.
	 *
	 * @param int $total_count The total number of itmes being paginated
	 * @param View $view A View object for rendering the pagination data
	 */
	public function __construct($total_count, View $view)
	{
		$total_count = (int) $total_count;

		// Total count can be 0
		if ($total_count < 0)
		{
			throw new \InvalidArgumentException("The total_count argument must be greater than 0. \"{$total_count}\" was passed.");
		}

		$this->total_count = $total_count;
		$this->view = $view;
	}

	/**
	 * Sets the number of items per page
	 *
	 * @param int $per_page The number of items per page
	 * @return self This returns a reference to itself
	 */
	public function perPage($per_page)
	{
		$this->per_page = (int) $per_page;
		if ($this->per_page < 1)
		{
			throw new \InvalidArgumentException("The argument to perPage must be greater than 0. \"{$per_page}\" was passed.");
		}
		return $this;
	}

	/**
	 * Sets page number being displayed
	 *
	 * @param int $current_page	The current page (defaults to 1)
	 * @return self This returns a reference to itself
	 */
	public function currentPage($current_page)
	{
		$this->current_page = (int) $current_page;
		if ($this->current_page < 1)
		{
			throw new \InvalidArgumentException("The argument to currentPage must be greater than 0. \"{$current_page}\" was passed.");
		}
		return $this;
	}

	/**
	 * Sets the query string variable name
	 *
	 * @param string $page_variable	The name of the page variable in the query string
	 * @return self This returns a reference to itself
	 */
	public function queryStringVariable($page_variable)
	{
		$this->page_variable = (string) $page_variable;
		return $this;
	}

	/**
	 * Sets the number of numbered pages to calculate
	 *
	 * @param int $pages The number of numbered pages to calculate
	 * @return self This returns a reference to itself
	 */
	public function displayPageLinks($pages_to_display)
	{
		$this->pages_to_display = (int) $pages_to_display;
		if ($this->pages_to_display < 1)
		{
			throw new \InvalidArgumentException("The argument to displayPageLinks must be greater than 0. \"{$pages_to_display}\" was passed.");
		}
		return $this;
	}

	/**
	 * Renders the pagination to HTML
	 *
	 * @param object $base_url A CP\URL object
	 * @return string The rendered HTML of the pagination
	 */
	public function render(Url $base_url)
	{
		$prev  = ($this->current_page - 1 >= 1) ? ($this->current_page - 1) : NULL;
		$pages = (int) ceil($this->total_count / $this->per_page);
		$next  = ($this->current_page + 1 <= $pages) ? ($this->current_page + 1) : NULL;
		$last  = $pages;

		// Show no pagination unless we have at least 2 pages
		if ($pages < 2)
		{
			return;
		}

		// Remove the current page from the count and force an integer instead of a float.
		$pages_to_display = (int) $this->pages_to_display - 1;

		$links['total_count'] = $this->total_count;
		$links['current_page'] = $this->current_page;
		$links['first'] = $base_url->compile();
		foreach (array('prev', 'next', 'last') as $key)
		{
			if (${$key} === NULL) continue;

			$url = clone $base_url;
			$url->setQueryStringVariable((string) $this->page_variable, ${$key});
			$links[$key] = $url->compile();
		}

		$start = ($this->current_page - 1 > 1) ? $this->current_page - 1 : 1;
		if ($start + $pages_to_display <= $pages)
		{
			$end = $start + $pages_to_display;
		}
		else
		{
			$end = $pages;
			if ($end - $pages_to_display > 1)
			{
				$start = $end - $pages_to_display;
			}
			else
			{
				$start = 1;
			}
		}

		for ($i = $start; $i <= $end; $i++)
		{
			$url = clone $base_url;
			$url->setQueryStringVariable($this->page_variable, $i);
			$links['pages'][$i] = $url->compile();
		}

		return $this->view->render(array('pagination' => $links));
	}
}

// END CLASS

// EOF
