<?php
namespace EllisLab\ExpressionEngine\Service\URL;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine URLFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class URLFactory {

	protected $cp_url;

	protected $default_cp_url;

	/**
	 * @var string $session_id The session id
	 */
	protected $session_id;

	protected $site_index;

	protected $uri_string;

	/**
	 *
	 * @param string $session_id The session id
	 * @return void
	 */
	public function __construct($cp_url, $site_index, $uri_string, $session_id = NULL, $default_cp_url = '')
	{
		$this->cp_url = $cp_url;
		$this->site_index = $site_index;
		$this->uri_string = $uri_string;
		$this->session_id = $session_id;
		$this->default_cp_url = ($default_cp_url) ?: SELF;
	}

	public function make($path, $qs = array(), $cp_url = '', $session_id = NULL)
	{
		if ($session_id === NULL)
		{
			$session_id = $this->session_id;
		}

		$cp_url = ($cp_url) ?: $this->default_cp_url;

		return new URL($path, $session_id, $qs, $cp_url, $this->uri_string);
	}

	public function makeFromString($url)
	{
		$components = parse_url($url);

		if ($components === FALSE)
		{
			// On seriously malformed URLs, parse_url() may return FALSE.
		}

		$cp_url = parse_url($this->cp_url);

		$url_is_cp = FALSE;

		// Do we have a CP URL?
		if ( ! isset($components['host']))
		{
			$url_is_cp = TRUE;
		}
		elseif (isset($cp_url['host']) && $components['host'] == $cp_url['host'])
		{
			$url_is_cp = TRUE;
		}

		if ($url_is_cp == TRUE)
		{
			$qs = array();
			if (isset($components['query']))
			{
				parse_str($components['query'], $qs);

				// Remove the Session ID; the URL class will add it if needed.
				unset($qs['S']);

				$vars = array_keys($qs);

				$i = array_search('cp/', $vars);
				if ($i !== FALSE)
				{
					$path = $vars[$i];
					unset($qs[$path]);

					return $this->make($path, $qs);
				}
			}
		}

		return $this->make('', array('URL' => urlencode($url)), $this->site_index, 0);
	}

	public function getCurrentUrl()
	{
		return $this->makeFromString($this->uri_string);
	}

}
// EOF
