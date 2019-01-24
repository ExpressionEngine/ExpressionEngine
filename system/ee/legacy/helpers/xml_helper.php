<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * XML Helpers
 */

/**
 * Convert Reserved XML characters to Entities
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('xml_convert'))
{
	function xml_convert($str, $protect_all = FALSE)
	{
		$temp = '__TEMP_AMPERSANDS__';

		// Replace entities to temporary markers so that
		// ampersands won't get messed up
		$str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);

		if ($protect_all === TRUE)
		{
			$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
		}

		$str = str_replace(array("&","<",">","\"", "'", "-"),
							array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
							$str);

		// Decode the temp markers back to entities
		$str = preg_replace("/$temp(\d+);/","&#\\1;",$str);

		if ($protect_all === TRUE)
		{
			$str = preg_replace("/$temp(\w+);/","&\\1;", $str);
		}

		return $str;
	}
}

// EOF
