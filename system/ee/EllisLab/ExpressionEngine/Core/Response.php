<?php

namespace EllisLab\ExpressionEngine\Core;

class Response {

	protected $body = '';

	protected $status = 200;

	protected $headers = array();

	protected $compress = FALSE;

	/**
	 *
	 */
	public function setBody($str)
	{
		if (is_array($str))
		{
			$str = json_encode($str);
			$this->setHeader('Content-Type', 'application/json; charset=UTF-8');
		}

		$this->body = $str;
	}

	/**
	 *
	 */
	public function appendBody($str)
	{
		$this->body .= $str;
	}

	/**
	 *
	 */
	public function setHeader($header, $value = NULL)
	{
		if ( ! isset($value))
		{
			list($header, $value) = explode(':', $header, 2);
		}

		$this->headers[$header] = $value;
	}

	/**
	 *
	 */
	public function send()
	{
		if ( ! $this->body)
		{
			// smoke and mirrors to support the old style
			return $GLOBALS['OUT']->_display();
		}

		$this->sendHeaders();
		$this->sendBody();
	}

	/**
	 *
	 */
	public function enableCompression()
	{
		if ($this->supportCompression())
		{
			$this->compress = TRUE;
		}
	}

	/**
	 *
	 */
	public function disableCompression()
	{
		$this->compress = FALSE;
	}

	/**
	 *
	 */
	public function supportsCompression()
	{
		return (
			$this->clientSupportsCompression() &&
			$this->serverSupportsCompression()
		);
	}

	/**
	 *
	 */
	public function compressionEnabled()
	{
		return $this->compress == TRUE && $this->status != 304;
	}

	/**
	 *
	 */
	protected function sendHeaders()
	{
		foreach ($this->headers as $name => $value)
		{
			@header($name.': '.$value);
		}
	}

	/**
	 *
	 */
	protected function sendBody()
	{
		if ($this->compressionEnabled())
		{
			ob_start('ob_gzhandler');
		}

		echo $this->body;
	}

	/**
	 *
	 */
	protected function clientSupportsCompression()
	{
		$header = 'HTTP_ACCEPT_ENCODING';

		return (
			isset($_SERVER[$header]) &&
			strpos($_SERVER[$header], 'gzip') !== FALSE
		);

	}

	/**
	 *
	 */
	protected function serverSupportsCompression()
	{
		$zlib_enabled = (bool) @ini_get('zlib.output_compression');

		return $zlib_enabled == FALSE && extension_loaded('zlib');
	}
}

// EOF
