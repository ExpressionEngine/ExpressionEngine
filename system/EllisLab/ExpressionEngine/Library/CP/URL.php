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

	/**
	 * Create a CP Path
	 * @param	string	$path		The path (i.e. 'logs/cp')
	 * @param	string	$session_id The session id
	 * @param	mixed	$qs			Query string parameters [array|string]
	 */
	public function __construct($path, $session_id = '', $qs = array())
	{
		if (is_array($path) || (is_object($path) && ! method_exists($path, '__toString')))
		{
			throw new \InvalidArgumentException('The path argument must be a string.');
		}

		if (is_array($session_id) || (is_object($session_id) && ! method_exists($session_id, '__toString')))
		{
			throw new \InvalidArgumentException('The session_id argument must be a string.');
		}

		if (is_object($qs) && ! method_exists($qs, '__toString'))
		{
			throw new \InvalidArgumentException('The qs argument must be a string or an array.');
		}

		$this->path = (string) $path;
		$this->session_id = (string) $session_id;

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
	 * @param $key		string	The name of the query string variable
	 * @param $value	string	The value of the query string variable
	 */
	public function setQueryStringVariable($key, $value)
	{
		$this->qs[$key] = $value;
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

		$qs = $this->qs;

		if ($this->session_id)
		{
			$qs['S'] = $this->session_id;
		}

		$qs = http_build_query($qs, AMP);

		$path = rtrim('?/cp/'.$path, '/');

		return SELF.$path.rtrim('&'.$qs, '&');
	}
}