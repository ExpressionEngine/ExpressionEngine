<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Curl;

/**
 * Curl Request
 */
abstract class Request {

	protected $headers = array();

	public function __construct($url, $data, $callback = NULL)
	{
		if ( ! function_exists('curl_version'))
		{
			throw new \Exception(lang('curl_not_installed'));
		}

		$this->config = array(
			CURLOPT_URL => $url,
    		CURLOPT_RETURNTRANSFER => 1,
    		CURLOPT_HEADER => 1,
		);

		foreach ($data as $key => $val)
		{
			if (substr($key, 0, 7) == "CURLOPT")
			{
				$this->config[constant($key)] = $val;
			}
		}

		if ( ! empty($callback))
		{
			$this->callback = $callback;
		}
	}

	public function exec()
	{
		$curl = curl_init();
		curl_setopt_array($curl, $this->config);

		$response = curl_exec($curl);

		if ($response === FALSE)
		{
			throw new \Exception(curl_error($curl));
		}

		list($headers, $data) = explode("\r\n\r\n", $response, 2);

		$this->setHeaders($headers, $curl);

		curl_close($curl);

		if ( ! empty($this->callback))
		{
			return call_user_func($this->callback, $data);
		}

		return $this->callback($data);
	}

	public function callback($data)
	{
		return $data;
	}

	/**
	 * Given a string of headers from a cURL response, creates an associative array
	 * class property of the headers
	 *
	 * @param	string		$headers	String output of headers from cURL
	 * @param	resource	$curl		Current cURL resource
	 */
	protected function setHeaders($headers, $curl)
	{
		$this->headers = curl_getinfo($curl);

		$headers = explode("\r\n", $headers);
		array_shift($headers);
		foreach ($headers as $i => $line)
		{
			list($key, $value) = explode(': ', $line);

			$this->headers[$key] = $value;
		}
	}

	/**
	 * Returns the requested header value
	 *
	 * @param	string	$key	Header to return value for
	 * @return	string	Value of header, or FALSE if header not found
	 */
	public function getHeader($key)
	{
		if (isset($this->headers[$key]))
		{
			return $this->headers[$key];
		}

		return FALSE;
	}

}

// EOF
