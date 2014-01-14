<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Date Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

/**
 * This calculcates the amount of time that has elapsed between two dates
 *
 * @access	public
 * @param	int			$timestamp	Unix timestamp of the date for calculation
 * @param	int			$referent	Unix timestamp of the date being compared against (default: time())
 * @param	string[]	$units_to_calculate		An array of units (date parts) to calculate
 * @return	int[]	An associative array of date units
 *  	e.g. 'minutes'  => '1'
 * 		     'seconds'  => '3'
 */
if ( ! function_exists('timespan_units'))
{
	function timespan_units($timestamp = 0, $referent = NULL, $units_to_calculate = NULL)
	{
		$units = array(
			'years'   => 0,
			'months'  => 0,
			'weeks'   => 0,
			'days'    => 0,
			'hours'   => 0,
			'minutes' => 0,
			'seconds' => 0
		);

		// Sanitizing the parameters
		if ( ! is_numeric($timestamp))
		{
			$timestamp = 0;
		}

		if ( ! is_numeric($referent))
		{
			$referent = time();
		}

		if ( ! is_array($units_to_calculate))
		{
			$units_to_calculate = array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds');
		}

		$delta = abs($timestamp - $referent);

		if (in_array('years', $units_to_calculate))
		{
			$units['years'] = (int) floor($delta / 31536000);
			$delta -= $units['years'] * 31536000;
		}

		if (in_array('months', $units_to_calculate))
		{
			$units['months'] = (int) floor($delta / 2628000);
			$delta -= $units['months'] * 2628000;
		}

		if (in_array('weeks', $units_to_calculate))
		{
			$units['weeks'] = (int) floor($delta / 604800);
			$delta -= $units['weeks'] * 604800;
		}

		if (in_array('days', $units_to_calculate))
		{
			$units['days'] = (int) floor($delta / 86400);
			$delta -= $units['days'] * 86400;
		}

		if (in_array('hours', $units_to_calculate))
		{
			$units['hours'] = (int) floor($delta / 3600);
			$delta -= $units['hours'] * 3600;
		}

		if (in_array('minutes', $units_to_calculate))
		{
			$units['minutes'] = (int) floor($delta / 60);
			$delta -= $units['minutes'] * 60;
		}

		if (in_array('seconds', $units_to_calculate))
		{
			$units['seconds'] = $delta;
		}

		return $units;
	}
}

// ------------------------------------------------------------------------

/**
 * Generates a human readable relative date string based on at least one timestamp
 *  	e.g. 'a day'
 *  	     '5 hours'
 *
 * @access	public
 * @param	int			$timestamp	Unix timestamp of the date for calculation
 * @param	int			$referent	Unix timestamp of the date being compared
 *                                  against (default: time())
 * @param	string		$singular	The text to use when the calculated value
 *                                  of a unit is 1
 * @param	string		$less_than	The text to use when the timespan is less
 *                                  than the smallest unit being calculated
 * @param	string[]	$units_to_calculate		An array of units (date parts)
 *                                              to calculate
 * @param	int			$depth		Determines how many date parts we use
 * @return	string	A human readable relative date string
 */
if ( ! function_exists('relative_date'))
{
	function relative_date($timestamp = 0, $referent = NULL, $singular = 'one', $less_than = 'less than', $units_to_calculate = NULL, $depth = NULL)
	{
		$units = array();

		$calculated_units = timespan_units($timestamp, $referent, $units_to_calculate);

		foreach (array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds') as $key)
		{
			if ($calculated_units[$key] == 1)
			{
				$units[] = $singular.' '.lang(rtrim($key, 's'));
			}
			elseif ($calculated_units[$key] > 1)
			{
				$units[] = $calculated_units[$key].' '.lang(rtrim($key));
			}
		}

		if (empty($units))
		{
			$unit = is_array($units_to_calculate) ? end($units_to_calculate) : 'seconds';
			return $less_than.' '.$singular.' '.lang(rtrim($unit, 's'));
		}

		if (is_numeric($depth))
		{
			$units = array_slice($units, 0, $depth);
		}

		if (count($units) > 1)
		{
			$i = count($units) - 1;
			$units[$i] = lang('and').' '.$units[$i];
		}

		$str = implode(', ', $units);

		if (count($units) < 3)
		{
			$str = str_replace(',', '', $str);
		}

		return $str;
	}
}

/* End of file EE_date_helper.php */
/* Location: ./system/expressionengine/helpers/EE_date_helper.php */