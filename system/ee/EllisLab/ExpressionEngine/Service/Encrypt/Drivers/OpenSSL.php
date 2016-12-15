<?php
namespace EllisLab\ExpressionEngine\Service\Encrypt\Drivers;

use EllisLab\ExpressionEngine\Service\Encrypt\Driver;
use \InvalidArgumentException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.5.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine OpenSSL Driver Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class OpenSSL implements Driver {

	protected $method = "AES-256-ECB";
	protected $options = OPENSSL_RAW_DATA;
	protected $iv;

	public function __construct()
	{
		$this->setInitializationVector();
	}

	public function setInitializationVector()
	{
		$this->iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
	}

	public function setMethod($method)
	{
		if (in_array($method, openssl_get_cipher_methods(true)))
		{
			$this->method = $method;
			$this->setInitializationVector();
		}
		else
		{
			throw new InvalidArgumentException('{$method} is not a valid encryption method.');
		}

		return $this;
	}

	public function encode($string, $key)
	{
		return openssl_encrypt($string, $this->method, $key, $this->options, $this->iv);
	}

	public function decode($data, $key)
	{
		return openssl_decrypt($data, $this->method, $key, $this->options, $this->iv);
	}

}

// EOF
