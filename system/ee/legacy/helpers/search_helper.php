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
 * Search Helpers
 */

/**
 * Sanitize Search Terms
 *
 * Filters a search string for security
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('sanitize_search_terms'))
{
	function sanitize_search_terms($str)
	{
		//$str = strtolower($str);
		$str = strip_tags($str);

		// We allow some words with periods.
		// This array defines them.
		// Note:  Do not include periods in the array.

		$allowed = array(
							'Mr',
							'Ms',
							'Mrs',
							'Dr'
						);

		foreach ($allowed as $val)
		{
			$str = str_replace($val.".", $val."T9nbyrrsXCXv0pqemUAq8ff", $str);
		}

		// Remove periods unless they are within a word
		$str = preg_replace("#\.*(\s|$)#", " ", $str);

		// These are disallowed characters
		$chars = array(
						","	,
						"("	,
						")"	,
						"+"	,
						"!"	,
						"?"	,
						"["	,
						"]"	,
						"@"	,
						"^"	,
						"~"	,
						"*"	,
						"|"	,
						"\n",
						"\t"
					  );


		$str = str_replace($chars, ' ', $str);
		$str = preg_replace("(\s+)", " ", $str);

		// Put allowed periods back
		$str = str_replace('T9nbyrrsXCXv0pqemUAq8ff', '.', $str);

		// Kill naughty stuff...
		$str = ee('Security/XSS')->clean($str);

		return trim($str);
	}
}

// EOF
