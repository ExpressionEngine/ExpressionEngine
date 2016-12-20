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

	/**
	 * @var string $method The encryption method OpenSSL will use
	 */
	protected $method = "AES-256-ECB";

	/**
	 * @var int $options The option OpenSSL will use
	 */
	protected $options = OPENSSL_RAW_DATA;

	/**
	 * @var string $iv Our initialization vector
	 */
	protected $iv;

	/**
	 * Constructor; generates our initialization vector
	 */
	public function __construct()
	{
		$this->generateInitializationVector();
	}

	/**
	 * Genrates an initialization vector based on the encryption method
	 *
	 * @return self This returns a reference to itself
	 */
	protected function generateInitializationVector()
	{
		$this->iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
		return $this;
	}

	/**
	 * Sets the encryption method and regenerates the initialization vector
	 *
	 * @throws InvalidARgumentException if the method is invalid
	 * @param string $method The encryption method
	 * @return self This returns a reference to itself
	 */
	public function setMethod($method)
	{
		if (in_array($method, openssl_get_cipher_methods(true)))
		{
			$this->method = $method;
			$this->generateInitializationVector();
		}
		else
		{
			throw new InvalidArgumentException('{$method} is not a valid encryption method.');
		}

		return $this;
	}

	/**
	 * Takes a plain-text string and key and encrypts it
	 *
	 * @param string $string The plaintext string
	 * @param string $key The secret key
	 * @return string|FALSE The encrypted string on success or FALSE on failure
	 */
	public function encode($string, $key)
	{
		return openssl_encrypt($string, $this->method, $key, $this->options, $this->iv);
	}

	/**
	 * Takes an encrypted string and key and decrypts it.
	 *
	 * @param string $data The encrypted string
	 * @param string $key The secret key
	 * @return string|FALSE the decrypted string on success or FALSE on failure
	 */
	public function decode($data, $key)
	{
		return openssl_decrypt($data, $this->method, $key, $this->options, $this->iv);
	}

	/**
	 * Stub since the interface requires it.
	 *
	 * @return self This returns a reference to itself
	 */
	public function setHashObject($obj)
	{
		return $this;
	}

}

// EOF
