<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

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
 * ExpressionEngine Site Filter Class
 *
 * This will provide the HTML for a filter that will list a set of sites as well
 * as a custom <input> element for searching for a site.
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Site extends Filter {

	public function __construct()
	{
		$this->name = 'filter_by_site';
		$this->label = 'site';
		$this->placeholder = lang('filter_by_site');
		$this->options = ee()->session->userdata('assigned_sites');
	}

	/**
	 * Validation: is the value in our list of options?
	 */
	public function isValid()
	{
		return (array_key_exists($this->value(), $this->options));
	}

	/**
	 * @see Filter::render for render behavior.
	 *
	 * Overrides the abstract render behavior by returning an empty string
	 * if multtiple sites are not available.
	 */
	public function render()
	{
		if (ee()->config->item('multiple_sites_enabled') !== 'y' || IS_CORE)
		{
			return '';
		}

		parent::render();
	}

}
// EOF