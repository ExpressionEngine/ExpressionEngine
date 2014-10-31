<?php
namespace EllisLab\ExpressionEngine\Service\CP\Filter;

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
 * ExpressionEngine Perpage Filter Class
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

	public function isValid()
	{
		if (array_key_exists($this->value(), $this->options))
		{
			return TRUE;
		}

		return FALSE;
	}

	public function render()
	{
		if (ee()->config->item('multiple_sites_enabled') !== 'y' || IS_CORE)
		{
			return '';
		}

		parent::render();
	}

}
// END CLASS

/* End of file Site.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/Site.php */