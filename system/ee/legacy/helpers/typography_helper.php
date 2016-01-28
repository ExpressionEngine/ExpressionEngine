<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * CodeIgniter Typography Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/typography_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Convert newlines to HTML line breaks except within PRE tags
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('nl2br_except_pre'))
{
	function nl2br_except_pre($str)
	{
		ee()->load->library('typography');

		return ee()->typography->nl2br_except_pre($str);
	}
}

// ------------------------------------------------------------------------

/**
 * Auto Typography Wrapper Function
 *
 *
 * @access	public
 * @param	string
 * @param	bool	whether to allow javascript event handlers
 * @param	bool	whether to reduce multiple instances of double newlines to two
 * @return	string
 */
if ( ! function_exists('auto_typography'))
{
	function auto_typography($str, $strip_js_event_handlers = TRUE, $reduce_linebreaks = FALSE)
	{
		ee()->load->library('typography');
		return ee()->typography->auto_typography($str, $strip_js_event_handlers, $reduce_linebreaks);
	}
}


// --------------------------------------------------------------------

/**
 * HTML Entities Decode
 *
 * This function is a replacement for html_entity_decode()
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('entity_decode'))
{
	function entity_decode($str, $charset='UTF-8')
	{
		return ee('Security/XSS')->entity_decode($str, $charset);
	}
}

// EOF
