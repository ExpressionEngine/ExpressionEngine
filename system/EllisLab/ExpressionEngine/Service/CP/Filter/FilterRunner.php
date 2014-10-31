<?php
namespace EllisLab\ExpressionEngine\Service\CP\Filter;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\URL;

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
 * ExpressionEngine FilterRunner Class
 *
 * @package		ExpressionEngine
 * @subpackage	Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FilterRunner {
	public $filters;

	private $url;
	private $parameters;

	/**
	 * Constructor
	 *
	 * @param obj	$url		A CP/URL object that serves as the base URL for
	 *                  		the filters
	 * @param array	$filters	An array of Filter objects
	 */
	public function __construct(URL $url, array $filters = array())
	{
		$this->url = $url;
		$this->filters = $filters;

		$this->parameters = NULL;
	}

	/**
	 * Fetches and returns the GET/POST (or default) parameters related to the
	 * filters
	 *
	 * @return array	An associative array of submitted values in the form
	 *     'filter_by_field' => 'foo'
	 */
	public function getParameters()
	{
		if (is_null($this->parameters))
		{
			$this->getFilterValues();
		}

		return $this->parameters;
	}

	/**
	 * With the base URL provided in the constructor, this will apply any
	 * submitted parameters and return a URL object.
	 *
	 * @return obj	A CP/URL object
	 */
	public function getUrl()
	{
		$this->getFilterValues();
		$url = clone $this->url;
		foreach ($this->parameters as $key => $value)
		{
			$url->setQueryStringVariable($key, $value);
		}
		return $url;
	}

	/**
	 * Loops through all the filters and generates HTML
	 *
	 * @return str	The HTML for the filters
	 */
	public function render()
	{
		if (empty($this->filters))
		{
			return '';
		}

		$url = $this->getUrl();
		$filters = array();

		foreach ($this->filters as $filter)
		{
			$filters[] = $filter->render($url);
		}

		return ee()->load->view('_shared/filters/filters', array('filters' => $filters), TRUE);
	}

	/**
	 * Loops thorugh the filters and stores the submitted/default values
	 *
	 * @return void
	 */
	private function getFilterValues()
	{
		$this->parameters = array();
		foreach ($this->filters as $filter)
		{
			$value = $filter->value();

			if ($value)
			{
				$this->parameters[$filter->name] = $value;
			}
		}
	}
}
// END CLASS

/* End of file FilterRunner.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/FilterRunner.php */