<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core URI Helper Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_URI extends CI_URI {

	var $query_string		= 'index';	// Only the query segment of the URI: 124
	var $page_query_string	= '';		// For a Pages request, this contains the Entry ID for the Page
	var $session_id			= '';

	// These are reserved words that have special meaning when they are the first
	// segment of a URI string.  Template groups can not be named any of these words
	var $reserved  = array('css');

	/**
	 * Fetch uri string extension
	 *
	 * We hook into fetch_uri_string to look for a session id in the $_GET
	 * array, before passing it on to CI to figure out a url. Doing it after
	 * CI did not work with query strings and auto since key($_GET) comes out
	 * as /S, which is not a good path
	 *
	 * @access	private
	 * @return	void
	 */
	function _fetch_uri_string()
	{
		$key = FALSE;

		if (is_array($_GET))
		{
			if (isset($_GET['S']))
			{
				$key = 'S';
			}
			elseif (trim(key($_GET), '/') == 'S')
			{
				$key = key($_GET);
			}
		}

		if ($key)
		{
			$val = $_GET[$key];
			unset($_GET[$key]);

			$x = explode('/', $val);

			// Set the session ID
			$this->session_id = array_shift($x);

			$leftovers = implode('/', $x);

			if ($leftovers)
			{
				$_GET = array($leftovers => '1') + $_GET;
			}
		}

		return parent::_fetch_uri_string();
	}

	// --------------------------------------------------------------------

	/**
	 * Explode the URI Segments. The individual segments will
	 * be stored in the $this->segments array.
	 *
	 * THIS FUNCTION OVERRIDES THE FUNCTION IN THE CI URI CLASS.  WE NEED TO
	 * DO THIS IN ORDER TO DEAL WITH EE SESSION ID'S AND A COUPLE OTHER THINGS
	 * NOT NATIVE TO CI
	 *
	 * @access	private
	 * @return	void
	 */
	function _explode_segments()
	{
		if ($this->uri_string == '')
		{
			return;
		}

		$zero_index = 0;

		// Turn the URI segments into an array
		$segs = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string));

		// Is there a session ID in the first segment?
		// If so we will extract it and remove the data from the URI and the segment arrays
		if (substr($segs[0], 0, 2) == 'S=')
		{
			// Set the session ID
			$this->session_id = substr($segs[0], 2);

			// Remove the session ID from the full URI string
			$this->uri_string = trim(str_replace($segs[0], '', $this->uri_string), '/');

			// Kill the session ID from the exploded segments
			unset($segs[0]);

			// Since we no longer have a zero index we change it to 1
			$zero_index = 1;
		}

		// Is there a reason to continue?
		if (count($segs) == 0)
		{
			$this->uri_string = '';
			return;
		}

		// Safety Check:  If the URL contains more than 9 segments we'll show an error message
		if (count($segs) > 9)
		{
			show_error("Error: The URL contains too many segments.", 404);
		}


		// Is the first URI segment reserved?
		// Reserved segments are treated as Action requests so we'll assign them as $_GET variables.
		// We do this becuase these reserved words are actually Action requests that don't come to
		// us as normal GET/POST requests.
		if (in_array($segs[$zero_index], $this->reserved))
		{
			$_GET['ACT'] = $segs[$zero_index];

			for ($i = $zero_index; $i < count($segs); $i++)
			{
				$_GET['ACT_'.$i] = $segs[$i];
			}
		}

		// Does the URI contain the css request? If so, assign it as a GET variable.
		// This only happens when the "force query string" preference is set.
		if (substr($segs[$zero_index], 0, 2) == 'css=')
		{
			$_GET['css'] = substr($this->uri_string, 4);

			// Remove css= from the first segment
			$segs[$zero_index] = substr($segs[$zero_index], 4);
		}

		// Add the slashes back to the URI string
		// $this->uri_string = '/'.$this->uri_string.'/';

		// Compile the segments into the segment array and rebuild the URI string
		$uri = '';
		foreach($segs as $val)
		{
			// Filter segments for security
			$val = trim($this->_filter_uri(urldecode($val)));

			if ($val != '')
			{
				$this->segments[] = $val;
				$uri .= $val.'/';
			}
		}

		$this->uri_string = trim($uri, '/');

		// Determine the "query string" and set it
		if ( ! isset($this->segments[1]))
		{
			$this->query_string = 'index';
		}
		elseif ( ! isset($this->segments[2]))
		{
			$this->query_string = $this->segments[1];
		}
		else
		{
			$this->query_string = preg_replace("|".'/'.preg_quote($this->segments[0]).'/'.preg_quote($this->segments[1])."|", '', $this->uri_string);
		}

		$this->query_string = trim($this->query_string, '/');
	}

	// --------------------------------------------------------------------

	/**
	 * Filter segments for malicious characters
	 * For EE, since segments can be used in tag parameters, we do a little
	 * extra here that we do not need in CI
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _filter_uri($str)
	{
		if ($str == '')
		{
			return $str;
		}

		$str = str_replace(array("\r", "\r\n", "\n", '%3A','%3a','%2F','%2f'), array('', '', '', ':', ':', '/', '/'), $str);

		if (preg_match("#(;|\?|{|}|<|>|http:\/\/|https:\/\/|\w+:/*[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $str))
		{
			show_error('The URI you submitted has disallowed characters.', 400);
    	}

		if (strpos($str, '=') !== FALSE && preg_match('#.*(\042|\047).+\s*=.*#i', $str))
		{
			$str = str_replace(array('"', "'", ' ', '='), '', $str);
		}

		// Convert programatic characters to entities
		$bad	= array('$', 		'(', 		')',	 	'%28', 		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');

		return str_replace($bad, $good, $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Reformat our old ugly urls to something a little more elegant.
	 *
	 * @access	private
	 * @param	string  $old   Old ugly cp url
	 * @param   string  $base  Current base url, to make hte login redirect work
	 * @return	string  New pretty cp url
	 */
	public function reformat($old, $base = NULL)
	{
		$new = str_replace(AMP, '&', $old);

		// cp use only
		if (REQ != 'CP')
		{
			return $new;
		}

		if ( ! isset($base))
		{
			$base = BASE;
		}

		$base = str_replace(AMP, '&', $base);

		// base not found? non-cp url or in the new format already
		if (strpos($new, $base) !== 0)
		{
			return $new;
		}

		if (preg_match('/(.*?)[?](.*?&)?(D=cp(?:&C=[^&]+(?:&M=[^&]+)?)?)(?:&(.+))?$/', $new, $matches))
		{
			// matches[1] : index.php
			// matches[2] : S=49204&
			// matches[3] : D=cp&C=foo&M=bar
			// matches[4] : &foobarbaz
			$matches = array_merge($matches, array(
				'', '', '', ''
			));

			$session_id = trim($matches[2], '&');
			$controller = trim($matches[3], '&');
			$query_str  = trim($matches[4], '&');

			$controller = preg_replace('/&?[DCM]=/', '/', $controller);

			if ($session_id == 'S=0')
			{
				$session_id = '';
			}

			$query_str = trim($query_str.'&'.$session_id, '&');
			$query_str = $query_str ? '&'.$query_str : '';

			$new = $matches[1].'?'.$controller.$query_str;
		}

		return $new;
	}
}
// END CLASS

/* End of file EE_URI.php */
/* Location: ./system/expressionengine/libraries/EE_URI.php */