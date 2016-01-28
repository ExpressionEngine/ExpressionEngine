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
 * CodeIgniter Security Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/security_helper.html
 */

// ------------------------------------------------------------------------

/**
 * XSS Filtering
 *
 * @access	public
 * @param	string
 * @param	bool	whether or not the content is an image file
 * @return	string
 */
if ( ! function_exists('xss_clean'))
{
	function xss_clean($str, $is_image = FALSE)
	{
		return ee('Security/XSS')->clean($str, $is_image);
	}
}

// ------------------------------------------------------------------------

/**
 * Sanitize Filename
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('sanitize_filename'))
{
	function sanitize_filename($filename)
	{
		return ee()->security->sanitize_filename($filename);
	}
}

// ------------------------------------------------------------------------

/**
 * Strip Image Tags
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('strip_image_tags'))
{
	function strip_image_tags($str)
	{
		$str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
		$str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
 * Convert PHP tags to entities
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('encode_php_tags'))
{
	function encode_php_tags($str)
	{
		$str = str_replace(array('<?php', '<?PHP', '<?', '?>', '<%', '%>'),
		                   array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;', '&lt;%', '%&gt;'),
		                   $str);

		if (stristr($str, '<script') &&
			preg_match_all("/<script.*?language\s*=\s*(\042|\047)?php(\\1)?.*?>.*?<\/script>/is", $str, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$str = str_replace($match, htmlspecialchars($match), $str);
			}
		}

		return $str;
	}
}

// EOF
