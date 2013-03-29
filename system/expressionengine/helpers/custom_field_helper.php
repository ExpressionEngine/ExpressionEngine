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
 * ExpressionEngine Segment Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

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
		$data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), $val);
	}
	
	// Implode on seperator
	return implode('|', $data);
}

// ------------------------------------------------------------------------

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

/* End of file custom_field_helper.php */
/* Location: ./system/expressionengine/helpers/custom_field_helper.php */
