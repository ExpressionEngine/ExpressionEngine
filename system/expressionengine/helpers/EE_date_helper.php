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
 * @param	string[]	$units		An array of units (date parts) to calculate
 * @return	int[]	An associative array of date parts
 *  	e.g. 'minutes'  => '1'
 * 		     'seconds'  => '3'
 */
if ( ! function_exists('timespan_parts'))
{
	function timespan_parts($timestamp = 0, $referent = NULL, $units = NULL)
	{
		$date_parts = array(
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

		if ( ! is_array($units))
		{
			$units = array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds');
		}

		$delta = abs($timestamp - $referent);

		if (in_array('years', $units))
		{
			$date_parts['years'] = (int) floor($delta / 31536000);
			$delta -= $date_parts['years'] * 31536000;
		}

		if (in_array('months', $units))
		{
			$date_parts['months'] = (int) floor($delta / 2628000);
			$delta -= $date_parts['months'] * 2628000;
		}

		if (in_array('weeks', $units))
		{
			$date_parts['weeks'] = (int) floor($delta / 604800);
			$delta -= $date_parts['weeks'] * 604800;
		}

		if (in_array('days', $units))
		{
			$date_parts['days'] = (int) floor($delta / 86400);
			$delta -= $date_parts['days'] * 86400;
		}

		if (in_array('hours', $units))
		{
			$date_parts['hours'] = (int) floor($delta / 3600);
			$delta -= $date_parts['hours'] * 3600;
		}

		if (in_array('minutes', $units))
		{
			$date_parts['minutes'] = (int) floor($delta / 60);
			$delta -= $date_parts['minutes'] * 60;
		}

		if (in_array('seconds', $units))
		{
			$date_parts['seconds'] = $delta;
		}

		return $date_parts;
	}
}

// ------------------------------------------------------------------------

/**
 * Generates a human readable relative date string based on at least one timestamp
 *  	e.g. 'a day ago'
 *  	      'in 5 hours'
 *
 * @access	public
 * @param	int			$timestamp	Unix timestamp of the date for calculation
 * @param	int			$referent	Unix timestamp of the date being compared against (default: time())
 * @param	string[]	$units		An array of units (date parts) to calculate
 * @param	int			$depth		Determines how many date parts we use
 * @return	string	A human readable relative date string
 */
if ( ! function_exists('timespan'))
{
	function timespan($timestamp = 0, $referent = NULL, $units = NULL, $depth = NULL)
	{
		$CI =& get_instance();
		$CI->lang->load('date');

		$date_parts = array();
		$str = '';

		$parts = timespan_parts($timestamp, $referent, $units);

		foreach (array('years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds') as $key)
		{
			if ($parts[$key] == 1)
			{
				$date_parts[] = $parts[$key].' '.$CI->lang->line(rtrim($key, 's'));
			}
			elseif ($parts[$key] > 1)
			{
				$date_parts[] = $parts[$key].' '.$CI->lang->line(rtrim($key));
			}
		}

		if (is_numeric($depth))
		{
			$date_parts = array_slice($date_parts, 0, $depth);
		}

		if (count($date_parts) > 1)
		{
			$i = count($date_parts) - 1;
			$date_parts[$i] = 'and '.$date_parts[$i];
		}

		$str = implode(', ', $date_parts);

		if (count($date_parts) < 3)
		{
			$str = str_replace(',', '', $str);
		}

		return $str;
	}
}

/* End of file EE_date_helper.php */
/* Location: ./system/expressionengine/helpers/EE_date_helper.php */