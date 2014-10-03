<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2006, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine URL Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

/**
 * Create a CP Path
 *
 * @param	string	$path				controller/method path
 * @param	mixed	$qs					query string [array|string]
 * @param	bool	$force_base_cp_url	whether to force the 'cp_url' base address on CP generated URLs
 * @return	string
 */
function cp_url($path, $qs = '', $force_base_cp_url = FALSE)
{
	$path = trim($path, '/');
	$path = preg_replace('#^cp(/|$)#', '', $path);

	if (is_array($qs))
	{
		$qs = http_build_query($qs, AMP);
	}

	$s = ee()->session->session_id();

	if ($s)
	{
		// Remove AMP from the beginning of the query string if it exists
		$qs = preg_replace('#^'.AMP.'#', '', $qs.AMP.'S='.$s);
	}

	$path = rtrim('?/cp/'.$path, '/');

	$base = (REQ == 'CP' && ! $force_base_cp_url) ? SELF : ee()->config->item('cp_url');

	return $base.$path.rtrim('&'.$qs, '&');
}

// ------------------------------------------------------------------------

/**
 * Create URL Title
 *
 * Takes a "title" string as input and creates a
 * human-friendly URL string with either a dash
 * or an underscore as the word separator.
 *
 * @review maybe roll into CI proper
 *
 * @access	public
 * @param	string	the string
 * @param	string	the separator: dash, or underscore
 * @return	string
 */
if ( ! function_exists('url_title'))
{
	function url_title($str, $separator = 'dash', $lowercase = FALSE)
	{
		if (UTF8_ENABLED)
		{
			$CI =& get_instance();
			$CI->load->helper('text');

			$str = utf8_decode($str);
			$str = preg_replace_callback('/(.)/', 'convert_accented_characters', $str);
		}

		$separator = ($separator == 'dash') ? '-' : '_';

		$trans = array(
						'&\#\d+?;'					=> '',
						'&\S+?;'					=> '',
						'\s+|/+'					=> $separator,
						'[^a-z0-9\-\._]'			=> '',
						$separator.'+'				=> $separator,
						'^[-_]+|[-_]+$'				=> '',
						'\.+$'						=> ''
					  );

		$str = strip_tags($str);

		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);
		}

		if ($lowercase === TRUE)
		{
			$str = strtolower($str);
		}

		return trim(stripslashes($str));
	}
}

// --------------------------------------------------------------------

/**
 * Anchor Link
 *
 * Creates an anchor based on the local URL.
 *
 * @access	public
 * @param	string	the URL
 * @param	string	the link title
 * @param	mixed	any attributes
 * @return	string
 */
function anchor($uri = '', $title = '', $attributes = '')
{
    $title = (string) $title;

    $site_url = is_array($uri) ? implode('/', $uri) : $uri;

    if (REQ != 'CP' && ! preg_match('!^\w+://! i', $site_url))
    {
        $site_url = ee()->functions->fetch_site_index(TRUE).$site_url;
    }

    if ($title == '')
    {
        $title = $site_url;
    }

    if ($attributes != '')
    {
        $attributes = _parse_attributes($attributes);
    }

    return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
}

// --------------------------------------------------------------------

/* End of file EE_url_helper.php */
/* Location: ./system/expressionengine/helpers/EE_url_helper.php */