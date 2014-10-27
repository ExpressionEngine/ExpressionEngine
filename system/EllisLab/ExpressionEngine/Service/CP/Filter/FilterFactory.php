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

	public static function dateFilter()
	{
		$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));

		ee()->javascript->set_global('date.date_format', $date_format);
		ee()->javascript->set_global('lang.date.months.full', array(
			lang('january'),
			lang('february'),
			lang('march'),
			lang('april'),
			lang('may'),
			lang('june'),
			lang('july'),
			lang('august'),
			lang('september'),
			lang('october'),
			lang('november'),
			lang('december')
		));
		ee()->javascript->set_global('lang.date.months.abbreviated', array(
			lang('jan'),
			lang('feb'),
			lang('mar'),
			lang('apr'),
			lang('may'),
			lang('june'),
			lang('july'),
			lang('aug'),
			lang('sept'),
			lang('oct'),
			lang('nov'),
			lang('dec')
		));
		ee()->javascript->set_global('lang.date.days', array(
			lang('su'),
			lang('mo'),
			lang('tu'),
			lang('we'),
			lang('th'),
			lang('fr'),
			lang('sa'),
		));
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/date-picker'),
		));

		$dates = array(
			'86400'     => ucwords(lang('last').' 24 '.lang('hours')),
			'604800'    => ucwords(lang('last').' 7 '.lang('days')),
			'2592000'   => ucwords(lang('last').' 30 '.lang('days')),
			'15552000'  => ucwords(lang('last').' 180 '.lang('days')),
			'31536000'  => ucwords(lang('last').' 365 '.lang('days')),
		);

		$filter = new Filter('filter_by_date', 'date', $dates);
		$filter->placeholder = lang('custom_date');
		$filter->attributes = array('rel' => 'date-picker');

		$value = $filter->getValue();
		if (array_key_exists($value, $dates))
		{
			$filter->setDisplayValue($dates[$value]);
		}
		else
		{
			$date = ee()->localize->string_to_timestamp($value);
			$filter->attributes['data-timestamp'] = $date;

			$filter->setDisplayValue(ee()->localize->format_date($date_format, $date));
			$filter->setValue(array($date, $date+86400));
		}

		return $filter;
	}
}
// END CLASS

/* End of file FilterFactory.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/CP/Filter/FilterFactory.php */