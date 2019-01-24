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
 * Custom Field Helper
 */

/**
 * Encode multi select field data
 *
 * Creates a pipe concatenated string with all superfluous pipes escaped
 *
 * @access	public
 * @param	array	the multi select data
 * @return	string
 */
function encode_multi_field($data = array())
{
	if ( ! is_array($data))
	{
		$data = array($data);
	}

	// Escape pipes
	foreach($data as $key => $val)
	{
		$data[$key] = is_array($val)
		    ? encode_multi_field($val)
		    : str_replace(array('\\', '|'), array('\\\\', '\|'), $val);
	}

	// Implode on seperator
	return implode('|', $data);
}

/**
 * Decode multi select field data
 *
 * Explodes the stored string and cleans up escapes
 *
 * @access	public
 * @param	string	data string
 * @return	array
 */
function decode_multi_field($data = '')
{
	if ($data == '')
	{
		return array();
	}

	if (is_array($data))
	{
		return $data;
	}

	// Explode at non-escaped pipes ([\\\\] == one backslash, thanks to php + regex escaping)
	$data = preg_split("#(?<![\\\\])[|]#", $data);

	// Reduce slashes
	return str_replace(array('\|', '\\\\'), array('|', '\\'), $data);
}

// EOF
