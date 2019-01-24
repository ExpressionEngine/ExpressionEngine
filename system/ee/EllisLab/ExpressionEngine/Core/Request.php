<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Core;

/**
 * Core Request
 */
class Request {

	protected $get;
	protected $post;
	protected $cookies;
	protected $files;
	protected $environment;

	public function __construct($get, $post, $cookies, $files, $environment)
	{
		$this->get = $get;
		$this->post = $post;
		$this->cookies = $cookies;
		$this->files = $files;
		$this->environment = $environment;
	}

	/**
	 * Build request from php globals
	 *
	 */
	public static function fromGlobals()
	{
		$environment = $_SERVER + $_ENV;

		return new static($_GET, $_POST, $_COOKIE, $_FILES, $environment);
	}

	/**
	 * Get a get value
	 *
	 * @param String $key the name of the get value
	 * @param Mixed $default Value to return if $key doesn't exist
	 * @return Mixed The get value [or $default]
	 */
	public function get($key, $default = NULL)
	{
		return $this->fetch('get', $key, $default);
	}

	/**
	 * Get a post value
	 *
	 * @param String $key the name of the post value
	 * @param Mixed $default Value to return if $key doesn't exist
	 * @return Mixed The post value [or $default]
	 */
	public function post($key, $default = NULL)
	{
		return $this->fetch('post', $key, $default);
	}

	/**
	 * Get a cookie value
	 *
	 * @param String $key the name of the cookie value
	 * @param Mixed $default Value to return if $key doesn't exist
	 * @return Mixed The cookie value [or $default]
	 */
	public function cookie($key, $default = NULL)
	{
		return $this->fetch('cookies', $key, $default);
	}

	/**
	 * Get a file value
	 *
	 * @param String $key the name of the file value
	 * @param Mixed $default Value to return if $key doesn't exist
	 * @return Mixed The file value [or $default]
	 */
	public function file($key, $default = NULL)
	{
		return $this->fetch('files', $key, $default);
	}

	/**
	 * Get a server value
	 *
	 * @param String $key the name of the server value
	 * @param Mixed $default Value to return if $key doesn't exist
	 * @return Mixed The server value [or $default]
	 */
	public function server($key, $default = NULL)
	{
		return $this->fetch('environment', $key, $default);
	}

	/**
	 * Get a header
	 *
	 * @param String $key the name of the header
	 * @param Mixed $default Value to return if header doesn't exist
	 * @return Mixed The header value [or $default]
	 */
	public function header($name, $default = NULL)
	{
		$name = str_replace('-', '_', $name);
		return $this->server("HTTP_{$name}", $default);
	}

	/**
	 * Get the request username
	 *
	 * @return String username
	 */
	public function username()
	{
		return $this->server('PHP_AUTH_USER')
			?: $this->server('REMOTE_USER')
			?: $this->server('AUTH_USER');
	}

	/**
	 * Get the request password
	 *
	 * @return String password
	 */
	public function password()
	{
		return $this->server('PHP_AUTH_PW')
			?: $this->server('REMOTE_PASSWORD')
			?: $this->server('AUTH_PASSWORD');
	}

	/**
	 * Get the request protocol
	 *
	 * @return String Request protocol and version
	 */
	public function protocol()
	{
		return $this->server('SERVER_PROTOCOL', 'HTTP/1.1');
	}

	/**
	 * Get the request method
	 *
	 * @return String Request method
	 */
	public function method()
	{
		return strtoupper($this->server('REQUEST_METHOD', 'GET'));
	}

	/**
	 * Is this a POST request?
	 *
	 * @return 	boolean
	 */
	public function isPost()
	{
		return ($this->method() == 'POST');
	}

	/**
	 * Is this a GET request?
	 *
	 * @return 	boolean
	 */
	public function isGet()
	{
		return ($this->method() == 'GET');
	}

	/**
	 * Get the request body
	 *
	 * @return String Request body
	 */
	public function body()
	{
		return file_get_contents('php://input');
	}

	/**
	 * Is ajax request?
	 *
	 * Test to see if a request contains the HTTP_X_REQUESTED_WITH header
	 *
	 * @return 	boolean
	 */
	public function isAjax()
	{
		return $this->header('X_REQUESTED_WITH') === 'XMLHttpRequest';
	}

	/**
	 * Is https request?
	 *
	 * @return bool Is https request?
	 */
	public function isEncrypted()
	{
		$https = $this->server('HTTPS');
		return $https != '' && strtolower($https) !== 'off';
	}

	/**
	 * Is a safe request
	 *
	 * @see RFC2616
	 * @return bool Is safe request method?
	 */
	public function isSafe()
	{
		return in_array(
			$this->method(),
			array('GET', 'POST', 'OPTIONS', 'TRACE')
		);
	}

	/**
	 * Helper method to get with default
	 *
	 * @param String $arr Class array name
	 * @param String $key Key to grab from the array
	 * @param Mixed $default Value to return if $key doesn't exist
	 * @return Mixed $key value or $default
	 */
	protected function fetch($arr, $key, $default)
	{
		if (array_key_exists($key, $this->$arr))
		{
			$source = $this->$arr;
			return $source[$key];
		}

		return $default;
	}
}

// EOF
