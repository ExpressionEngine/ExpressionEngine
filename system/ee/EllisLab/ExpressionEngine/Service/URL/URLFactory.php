<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\URL;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * URL Service Factory
 */
class URLFactory {

	/**
	 * @var string $cp_url The URL to the CP
	 */
	protected $cp_url;

	/**
	 * @var string $default_cp_url The default value to use for the $cp_url parameter when constructing a URL
	 */
	protected $default_cp_url;

	/**
	 * @var string $session_id The session id
	 */
	protected $session_id;

	/**
	 * @var string $site_index The URL to the site's index
	 */
	protected $site_index;

	/**
	 * @var string $uri_string The URI string from the HTTP request
	 */
	protected $uri_string;

	/**
	 * @var string $encrypt_delegate An Encryption Service delegate.
	 */
	protected $encrypt_delegate;

	/**
	 * Constructor
	 *
	 * @param string $cp_url The URL to the CP
	 * @param string $site_index The URL to the site's index
	 * @param string $uri_string The URI string from the HTTP request
	 * @param string|NULL $session_id The session id
	 * @param string $default_cp_url The default value to use for the $cp_url
	 *   parameter when constructing a URL
	 * @param obj $encrypt An Encryption Service instance
	 * @return void
	 */
	public function __construct($cp_url, $site_index, $uri_string, $session_id = NULL, $default_cp_url = '', $encrypt = NULL)
	{
		$this->cp_url = $cp_url;
		$this->site_index = $site_index;
		$this->uri_string = $uri_string;
		$this->session_id = $session_id;
		$this->default_cp_url = ($default_cp_url) ?: SELF;
		$this->encrypt_delegate = $encrypt;
	}

	/**
	 * Makes a URL object.
	 *
	 * @param string $path The path of the url (ie. 'publish/edit/2')
	 * @param array $qs An associative array of query string variables to append
	 *   to the rendered URL.
	 * @param string $cp_url The base URL to which all else will be appended (ie. 'admin.php')
	 * @param string|NULL $session_id A session ID to append to the rendered URL
	 * @return URL A URL object.
	 */
	public function make($path, $qs = array(), $cp_url = '', $session_id = NULL)
	{
		if ($session_id === NULL)
		{
			$session_id = $this->session_id;
		}

		$cp_url = ($cp_url) ?: $this->default_cp_url;

		return new URL($path, $session_id, $qs, $cp_url, $this->uri_string, $this->encrypt_delegate);
	}

	/**
	 * Makes a URL object from a string.
	 *
	 * @param string $url The URL to be parsed into a URL object
	 * @return URL A URL object.
	 */
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

				$arguments = array();
				$path = NULL;

				foreach ($qs as $key => $value)
				{
					if (strpos($key, '/cp/') === 0)
					{
						$path = $key;
						continue;
					}

					$arguments[$key] = $value;
				}

				if ($path)
				{
					return $this->make($path, $arguments);
				}
			}
		}

		return $this->make('', array('URL' => urlencode($url)), $this->site_index, 0);
	}

	/**
	 * Makes a URL object representing the requested URL.
	 *
	 * @return URL A URL object.
	 */
	public function getCurrentUrl()
	{
		$qs = $_GET;
		unset($qs['S'], $qs['D'], $qs['C'], $qs['M']);

		return $this->make($this->uri_string, $qs);
	}

	/**
	 * Decodes a base64 encoded, seralized URL object.
	 *
	 * @return URL A URL object or NULL
	 */
	public function decodeUrl($data)
	{
		$url = $this->make('');
		$success = $url->unserialize($this->encrypt_delegate->decode($data));

		return ($success) ? $url : NULL;
	}

}

// EOF
