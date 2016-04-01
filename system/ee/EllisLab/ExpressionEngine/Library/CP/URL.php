<?php
namespace EllisLab\ExpressionEngine\Library\CP;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine URL Class
 *
 * @package		ExpressionEngine
 * @subpackage	Library
 * @category	CP
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class URL implements \Serializable {

	/**
	 * @var string $path The path (i.e. 'logs/cp')
	 */
	public $path;

	/**
	 * @var string $session_id The session id
	 */
	public $session_id;

	/**
	 * @var array $qs An associative array of query string parameters
	 */
	public $qs = array();

	/**
	 * @var string $base The base part of the url which preceeds the path.
	 */
	public $base;

	/**
	 * @var string $requested_uri The URI string/path of where we are at now
	 */
	protected $requested_uri;

	/**
	 * Create a CP Path
	 *
	 * @param	string	$path		The path (i.e. 'logs/cp')
	 * @param	string	$session_id The session id
	 * @param	mixed	$qs			Query string parameters [array|string]
	 * @param	string	$cp_url		Optional value of cp_url config item,
	 *                        		include when creating CP URLs that are to
	 *                        		be used on the front end
	 */
	public function __construct($path, $session_id = NULL, $qs = array(), $cp_url = '', $requested_uri = '')
	{
		// PHP 5.3 will not throw an error on array to string conversion
		if (is_array($path) || is_array($session_id))
		{
			throw new \InvalidArgumentException("Invalid array to string conversion in " . get_called_class());
		}

		$this->path = (string) $path;
		$this->session_id = (string) $session_id;
		$this->base = (empty($cp_url)) ? SELF : (string) $cp_url;
		$this->requested_uri = $requested_uri;

		if (is_array($qs))
		{
			$this->qs = $qs;
		}
		else
		{
			parse_str(str_replace(AMP, '&', $qs), $this->qs);
		}
	}

	public function isTheRequestedURI()
	{
		return ('cp/' . $this->path == $this->requested_uri);
	}

	public function matchesTheRequestedURI()
	{
		return (strpos($this->requested_uri, $this->path) !== FALSE);
	}

	/**
	 * When accessed as a string simply complile the URL and return that
	 *
	 * @return string	The URL
	 */
	public function __toString()
	{
		return $this->compile();
	}

	/**
	 * Sets a value in the $qs array which will become the Query String of
	 * the request
	 *
	 * @param string $key   The name of the query string variable
	 * @param string $value	The value of the query string variable
	 * @return self This returns a reference to itself
	 */
	public function setQueryStringVariable($key, $value)
	{
		$this->qs[$key] = $value;
		return $this;
	}

	/**
	 * Sets a values in bulk in the $qs array which will become the Query String
	 * of the request
	 *
	 * @param array $values An associative array of keys and values
	 * @return self This returns a reference to itself
	 */
	public function addQueryStringVariables(array $values)
	{
		foreach ($values as $key => $value)
		{
			$this->setQueryStringVariable($key, $value);
		}
		return $this;
	}

	/**
	 * Compiles and returns the URL as a string. Typically this is used when you
	 * need to use a URL as an array key, or want to json_encode() a URL.
	 *
	 * @return string The URL
	 */
	public function compile()
	{
		// HACK: Really I'd rather have this outside the CP/URL class
		// or to have a more general URL class
		if (empty($this->path) && array_key_exists('URL', $this->qs))
		{
			return $this->base.'?URL=' . $this->qs['URL'];
		}

		$path = trim($this->path, '/');
		$path = preg_replace('#^cp(/|$)#', '', $path);

		if ($path)
		{
			$path = rtrim('?/cp/'.$path, '/');
		}

		$qs = $this->qs;

		if ($this->session_id)
		{
			$qs['S'] = $this->session_id;
		}

		$qs = ( ! empty($qs)) ? http_build_query($qs, AMP) : '';

		// Remove AMP from the beginning of the query string if it exists
		$qs = preg_replace('#^'.AMP.'#', '', $qs);

		return $this->base.$path.rtrim('&'.$qs, '&');
	}

	public function serialize()
	{
		$serialize = array(
			'path'          => $this->path,
			'session_id'    => $this->session_id,
			'qs'            => $this->qs,
			'base'          => $this->base,
			'requested_uri' => $this->requested_uri
		);

		return json_encode($serialize);
	}

	public function unserialize($serialized)
	{
		$data = json_decode($serialized, TRUE);

		$this->path = $data['path'];
		$this->session_id = $data['session_id'];
		$this->qs = $data['qs'];
		$this->base = $data['base'];
		$this->requested_uri = $data['requested_uri'];
	}

	public function encode()
	{
		return base64_encode(serialize($this));
	}
}

// EOF
