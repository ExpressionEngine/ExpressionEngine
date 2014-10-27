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
 * ExpressionEngine FilterFactory Class
 *
 * @package		ExpressionEngine
 * @subpackage	Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FilterFactory {

	public static function showFilter($total, $all_lang_key = 'all_items')
	{
		$filter = new Filter('perpage', 'show');
		$filter->placeholder = lang('custom_limit');
		$filter->setOptions(array(
				'25'  => '25 '.lang('results'),
				'50'  => '50 '.lang('results'),
				'75'  => '75 '.lang('results'),
				'100' => '100 '.lang('results'),
				'150' => '150 '.lang('results'),
				'all' => sprintf(lang($all_lang_key), $total)
			)
		);
		$filter->default_value = 20;

		return $filter;
	}

	public static function siteFilter()
	{
		if (ee()->config->item('multiple_sites_enabled') !== 'y' || IS_CORE)
		{
			return NULL;
		}

		$filter = new Filter('filter_by_site', 'site', ee()->session->userdata('assigned_sites'));
		$filter->placeholder = lang('filter_by_site');

		return $filter;
	}

	public static function usernameFilter()
	{
		$filter = new Filter('filter_by_username', 'username');
		$filter->placeholder = lang('filter_by_username');

		$members = ee()->api->get('Member')->all();
		if ($members)
		{
			$value = $filter->getValue();
			if ($value)
			{
				if (is_numeric($value))
				{
					$member = ee()->api->get('Member', $value)->first();
					if ($member)
					{
						$filter->setDisplayValue($member->username);
					}
				}
			}

			$options = array();

			foreach ($members as $member)
			{
				$options[$member->member_id] = $member->username;
			}

			$filter->setOptions($options);
		}

		return $filter;
	}

}
// END CLASS

/* End of file FilterFactory.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/FilterFactory.php */