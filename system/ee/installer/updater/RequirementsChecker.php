<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updater requirements checker class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RequirementsChecker
{
	private $requirements = array();
	private $minimum_php = '5.4.0';
	private $minimum_mysql = '5.0.3';

	public function __construct()
	{
		$this->requirements[] = new Requirement(
			'Your PHP version ('.phpversion().') does not meet the minimum requirement of '.$this->minimum_php.' for this version of ExpressionEngine.',
			version_compare(phpversion(), $this->minimum_php, '>=')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the JSON extension enabled.',
			function_exists('json_encode') && function_exists('json_decode')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the FileInfo extension enabled.',
			function_exists('finfo_open')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the cURL extension enabled.',
			function_exists('curl_version')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the OpenSSL extension enabled.',
			function_exists('openssl_verify')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the Mcrypt extension enabled.',
			function_exists('mcrypt_encrypt')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the imagejpeg() function to generate CAPTCHAs.',
			function_exists('imagejpeg')
		);

		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the GD extension enabled.',
			function_exists('gd_info')
		);
	}

	public function check()
	{
		// TODO: Loop over requirements and see if any are false
		return TRUE;
	}
}

class Requirement
{
	private $message;
	private $result;

	public function __construct($message, $result = FALSE)
	{
		$this->message = $message;
		$this->result = $result;
	}

	public function setCallback(Callable $callback)
	{
		$this->result = $callback();
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function getResult()
	{
		return $this->result;
	}
}

// EOF
