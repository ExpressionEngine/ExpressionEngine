<?php
namespace EllisLab\ExpressionEngine\Library\CP;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */

class URL {
	public $path;
	public $session_id;
	public $qs = array();
	public $base;

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
	public function __construct($path, $session_id = NULL, $qs = array(), $cp_url = '')
	{
		$this->path = (string) $path;
		$this->session_id = (string) $session_id;
		$this->base = (empty($cp_url)) ? SELF : (string) $cp_url;

		if (is_array($qs))
		{
			$this->qs = $qs;
		}
		else
		{
			parse_str(str_replace(AMP, '&', $qs), $this->qs);
		}
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
	 */
	public function setQueryStringVariable($key, $value)
	{
		$this->qs[$key] = $value;
	}

	/**
	 * Sets a values in bulk in the $qs array which will become the Query String
	 * of the request
	 *
	 * @param array $values An associative array of keys and values
	 */
	public function addQueryStringVariables(array $values)
	{
		foreach ($values as $key => $value)
		{
			$this->setQueryStringVariable($key, $value);
		}
	}

	/**
	 * Compiles and returns a URL
	 *
	 * @return string	The URL
	 */
	public function compile()
	{
		$path = trim($this->path, '/');
		$path = preg_replace('#^cp(/|$)#', '', $path);
		$path = rtrim('?/cp/'.$path, '/');

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
}

// END CLASS

/* End of file URL.php */
/* Location: ./system/EllisLab/ExpressionEngine/Library/CP/URL.php */
