<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2016, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Number Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/number_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Formats a numbers as bytes, based on size, and adds the appropriate suffix
 *
 * @access	public
 * @param	mixed	// will be cast as int
 * @return	string
 */
if ( ! function_exists('byte_format'))
{
	function byte_format($num, $precision = 1)
	{
		ee()->lang->load('number');

		if ($num >= 1000000000000)
		{
			$num = round($num / 1099511627776, $precision);
			$unit = ee()->lang->line('terabyte_abbr');
		}
		elseif ($num >= 1000000000)
		{
			$num = round($num / 1073741824, $precision);
			$unit = ee()->lang->line('gigabyte_abbr');
		}
		elseif ($num >= 1000000)
		{
			$num = round($num / 1048576, $precision);
			$unit = ee()->lang->line('megabyte_abbr');
		}
		elseif ($num >= 1000)
		{
			$num = round($num / 1024, $precision);
			$unit = ee()->lang->line('kilobyte_abbr');
		}
		else
		{
			$unit = ee()->lang->line('bytes');
			return number_format($num).' '.$unit;
		}

		return number_format($num, $precision).' '.$unit;
	}
}

// ------------------------------------------------------------------------

/**
 * Parse INI style size into bytes
 *
 * @param string $setting	INI formatted size
 * @return int				Size in bytes
 */
if ( ! function_exists('get_bytes'))
{
	function get_bytes($setting)
	{
		$setting = strtolower($setting);
		switch (substr($setting, -1))
		{
			case 'k':
				return (int) $setting * 1024;
			case 'm':
				return (int) $setting * 1048576;
			case 'g':
				return (int) $setting * 1073741824;
			default:
				return (int) $setting;
		}
	}
}

// EOF
