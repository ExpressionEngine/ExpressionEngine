<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine String Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------


 /**
 * Unique Marker
 *
 * The template library and some of our modules temporarily replace
 * pieces of code with a random string. These need to be unique per
 * request to avoid potential security issues.
 * 
 * @access	public
 * @param	string	marker identifier
 * @return	string
 */	
function unique_marker($ident)
{
	static $rand;
	
	if ( ! $rand)
	{
		$rand = random_string('alnum', 32);
	}
	
	return $rand.$ident;
}

// ----------------------------------------------------------------------------

/**
 * Just like trim, but also removes non-breaking spaces
 * 
 * @param string $string The string to trim
 * @return string The trimmed string
 */
function trim_nbs($string)
{
	return trim($string, " \t\n\r\0\xB\xA0".chr(0xC2).chr(0xA0));
}

// ----------------------------------------------------------------------------

/**
 * Returns the surrounding character of a string, if it exists
 * 
 * @param	string	$string		The string to check
 * @return	mixed	The surrounding character, or FALSE if there isn't one
 */
function surrounding_character($string)
{
	$first_char = substr($string, 0, 1);
	
	return ($first_char == substr($string, -1, 1)) ? $first_char : FALSE;
}

/* End of file EE_string_helper.php */
/* Location: ./system/expressionengine/helpers/EE_string_helper.php */