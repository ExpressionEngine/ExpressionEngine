<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core URI
 */
class EE_URI {

	var $uri_string;

	var	$keyval			= array();
	var $segments		= array();
	var $rsegments		= array();

	var $query_string		= 'index';	// Only the query segment of the URI: 124
	var $page_query_string	= '';		// For a Pages request, this contains the Entry ID for the Page
	var $session_id			= '';

	// These are reserved words that have special meaning when they are the first
	// segment of a URI string.  Template groups can not be named any of these words
	var $reserved  = array('css');

	/**
	 * Constructor
	 *
	 * Simply globalizes the $RTR object.  The front
	 * loads the Router class early on so it's not available
	 * normally as other classes are.
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->config = load_class('Config', 'core');
		log_message('debug', "URI Class Initialized");

		if (defined('REQ') && REQ == 'CP')
		{
			$this->config->set_item('uri_protocol', 'QUERY_STRING');
		}
	}


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

		$protocol = strtoupper($this->config->item('uri_protocol')) ?: 'AUTO';

		if ($protocol == 'AUTO')
		{
			// Let's try the REQUEST_URI first, this will work in most situations
			if ($uri = $this->_detect_uri('REQUEST_URI'))
			{
				$this->_set_uri_string($uri);
				return;
			}

			// Is there a PATH_INFO variable?
			// Note: some servers seem to have trouble with getenv() so we'll test it two ways
			$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
			if (trim($path, '/') != '' && $path != "/".SELF)
			{
				$this->_set_uri_string($path);
				return;
			}

			// No PATH_INFO?... What about QUERY_STRING?
			$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
			if (trim($path, '/') != '')
			{
				$this->_set_uri_string($path);
				return;
			}

			// As a last ditch effort lets try using the $_GET array
			if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '')
			{
				$this->_set_uri_string(key($_GET));
				return;
			}

			// We've exhausted all our options...
			$this->uri_string = '';
			return;
		}

		if ($protocol == 'REQUEST_URI' OR $protocol == 'QUERY_STRING')
		{
			$this->_set_uri_string($this->_detect_uri($protocol));
			return;
		}

		$path = (isset($_SERVER[$protocol]))
			? $_SERVER[$protocol]
			: @getenv($protocol);

		$this->_set_uri_string($path);
	}

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
		if (count($segs) > (config_item('max_url_segments') ?: 12))
		{
			show_error("The URL contains too many segments.", 404);
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

	/**
	 * Set the URI String
	 *
	 * @access	public
	 * @return	string
	 */
	function _set_uri_string($str)
	{
		// Filter out control characters
		$str = remove_invisible_characters($str, FALSE);

		// If the URI contains only a slash we'll kill it
		$this->uri_string = ($str == '/') ? '' : $str;
	}

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


	/**
	 * Detects the URI
	 *
	 * This function will detect the URI automatically and fix the query string
	 * if necessary.
	 *
	 * @access	private
	 * @param string $uri_protocol uri_protocol from the config
	 * @return	string
	 */
	protected function _detect_uri($uri_protocol)
	{
		if ($uri_protocol == 'REQUEST_URI')
		{
			if ( ! isset($_SERVER['REQUEST_URI']) OR ! isset($_SERVER['SCRIPT_NAME']))
			{
				return '';
			}

			$uri = $_SERVER['REQUEST_URI'];

			if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
			{
				$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
			}
			elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
			{
				$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}
		elseif ($uri_protocol == 'QUERY_STRING')
		{
			$uri = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		}

		// This section ensures that even on servers that require the URI to be
		// in the query string (Nginx) a correct URI is found, and also fixes
		// the QUERY_STRING server var and $_GET array.

		if (strncmp($uri, '?/', 2) === 0)
		{
			$uri = substr($uri, 2);
		}

		$parts = preg_split('#\?#i', $uri, 2);
		$uri = $parts[0];

		// If we're using QUERY_STRING, we may be steamrolling ACTION URIs
		if ($uri_protocol == "QUERY_STRING" && ! isset($parts[1]))
		{
			$test = array();
			parse_str($parts[0], $test);

			// If parse_str correctly parses the string, we need to rearrange
			// the query string and URI
			if (reset($test) != '')
			{
				$parts[1] = $uri;
				$uri = '';
			}
		}

		if (isset($parts[1]))
		{
			$_SERVER['QUERY_STRING'] = $parts[1];
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		}
		elseif (defined('REQ') && REQ == 'CP' && reset($_GET) === '')
		{
			$uri = $_SERVER['QUERY_STRING'] = key($_GET);
			array_shift($_GET);
		}
		else
		{
			$_SERVER['QUERY_STRING'] = '';
			$_GET = array();
		}

		if ($uri == '/' || empty($uri))
		{
			return '/';
		}

		$parsed_url = parse_url($uri);

		foreach (array('scheme', 'host', 'port', 'user', 'pass') as $component)
		{
			if (isset($parsed_url[$component]))
			{
				show_error('The URI you submitted is not allowed.', 400);
			}
		}

		$uri = (isset($parsed_url['path'])) ? $parsed_url['path'] : '/';

		// Do some final cleaning of the URI and return it
		return str_replace(array('//', '../'), '/', trim($uri, '/'));
	}

	/**
	 * Remove the suffix from the URL if needed
	 *
	 * @access	private
	 * @return	void
	 */
	function _remove_url_suffix()
	{
		if  ($this->config->item('url_suffix') != "")
		{
			$this->uri_string = preg_replace("|".preg_quote($this->config->item('url_suffix'))."$|", "", $this->uri_string);
		}
	}

	/**
	 * Re-index Segments
	 *
	 * This function re-indexes the $this->segment array so that it
	 * starts at 1 rather than 0.  Doing so makes it simpler to
	 * use functions like $this->uri->segment(n) since there is
	 * a 1:1 relationship between the segment array and the actual segments.
	 *
	 * @access	private
	 * @return	void
	 */
	function _reindex_segments()
	{
		array_unshift($this->segments, NULL);
		array_unshift($this->rsegments, NULL);
		unset($this->segments[0]);
		unset($this->rsegments[0]);
	}

	/**
	 * Fetch a URI Segment
	 *
	 * This function returns the URI segment based on the number provided.
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	function segment($n, $no_result = FALSE)
	{
		return ( ! isset($this->segments[$n])) ? $no_result : $this->segments[$n];
	}

	/**
	 * Fetch a URI "routed" Segment
	 *
	 * This function returns the re-routed URI segment (assuming routing rules are used)
	 * based on the number provided.  If there is no routing this function returns the
	 * same result as $this->segment()
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	function rsegment($n, $no_result = FALSE)
	{
		return ( ! isset($this->rsegments[$n])) ? $no_result : $this->rsegments[$n];
	}

	/**
	 * Generate a key value pair from the URI string
	 *
	 * This function generates and associative array of URI data starting
	 * at the supplied segment. For example, if this is your URI:
	 *
	 *	example.com/user/search/name/joe/location/UK/gender/male
	 *
	 * You can use this function to generate an array with this prototype:
	 *
	 * array (
	 *			name => joe
	 *			location => UK
	 *			gender => male
	 *		 )
	 *
	 * @access	public
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @return	array
	 */
	function uri_to_assoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'segment');
	}
	/**
	 * Identical to above only it uses the re-routed segment array
	 *
	 */
	function ruri_to_assoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'rsegment');
	}

	/**
	 * Generate a key value pair from the URI string or Re-routed URI string
	 *
	 * @access	private
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @param	string	which array we should use
	 * @return	array
	 */
	function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
	{
		if ($which == 'segment')
		{
			$total_segments = 'total_segments';
			$segment_array = 'segment_array';
		}
		else
		{
			$total_segments = 'total_rsegments';
			$segment_array = 'rsegment_array';
		}

		if ( ! is_numeric($n))
		{
			return $default;
		}

		if (isset($this->keyval[$n]))
		{
			return $this->keyval[$n];
		}

		if ($this->$total_segments() < $n)
		{
			if (count($default) == 0)
			{
				return array();
			}

			$retval = array();
			foreach ($default as $val)
			{
				$retval[$val] = FALSE;
			}
			return $retval;
		}

		$segments = array_slice($this->$segment_array(), ($n - 1));

		$i = 0;
		$lastval = '';
		$retval  = array();
		foreach ($segments as $seg)
		{
			if ($i % 2)
			{
				$retval[$lastval] = $seg;
			}
			else
			{
				$retval[$seg] = FALSE;
				$lastval = $seg;
			}

			$i++;
		}

		if (count($default) > 0)
		{
			foreach ($default as $val)
			{
				if ( ! array_key_exists($val, $retval))
				{
					$retval[$val] = FALSE;
				}
			}
		}

		// Cache the array for reuse
		$this->keyval[$n] = $retval;
		return $retval;
	}


	/**
	 * Generate a URI string from an associative array
	 *
	 *
	 * @access	public
	 * @param	array	an associative array of key/values
	 * @return	array
	 */
	function assoc_to_uri($array)
	{
		$temp = array();
		foreach ((array)$array as $key => $val)
		{
			$temp[] = $key;
			$temp[] = $val;
		}

		return implode('/', $temp);
	}

	/**
	 * Fetch a URI Segment and add a trailing slash
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	function slash_segment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'segment');
	}

	/**
	 * Fetch a URI Segment and add a trailing slash
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	function slash_rsegment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'rsegment');
	}

	/**
	 * Fetch a URI Segment and add a trailing slash - helper function
	 *
	 * @access	private
	 * @param	integer
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _slash_segment($n, $where = 'trailing', $which = 'segment')
	{
		$leading	= '/';
		$trailing	= '/';

		if ($where == 'trailing')
		{
			$leading	= '';
		}
		elseif ($where == 'leading')
		{
			$trailing	= '';
		}

		return $leading.$this->$which($n).$trailing;
	}

	/**
	 * Segment Array
	 *
	 * @access	public
	 * @return	array
	 */
	function segment_array()
	{
		return $this->segments;
	}

	/**
	 * Routed Segment Array
	 *
	 * @access	public
	 * @return	array
	 */
	function rsegment_array()
	{
		return $this->rsegments;
	}

	/**
	 * Total number of segments
	 *
	 * @access	public
	 * @return	integer
	 */
	function total_segments()
	{
		return count($this->segments);
	}

	/**
	 * Total number of routed segments
	 *
	 * @access	public
	 * @return	integer
	 */
	function total_rsegments()
	{
		return count($this->rsegments);
	}

	/**
	 * Fetch the entire URI string
	 *
	 * @access	public
	 * @return	string
	 */
	function uri_string()
	{
		return $this->uri_string;
	}

	/**
	 * Fetch the entire Re-routed URI string
	 *
	 * @access	public
	 * @return	string
	 */
	function ruri_string()
	{
		return '/'.implode('/', $this->rsegment_array()).'/';
	}
}
// END CLASS

// EOF
