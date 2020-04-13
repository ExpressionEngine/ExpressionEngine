<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Encrypt;

use \InvalidArgumentException;

/**
 * Encrypt Service
 */
class Encrypt {

	/**
	 * @var string $default_key The default encryption key to use when none is
	 * specified.
	 */
	private $default_key;

	/**
	 * @var string $method The encryption method OpenSSL will use
	 */
	protected $method = "AES-256-CBC";

	/**
	 * @var int $options The option OpenSSL will use
	 */
	protected $options = OPENSSL_RAW_DATA;

	/**
	* @var bool $mb_available Do we have the mbstring extension?
	*/
	protected $mb_available;

	/**
	 * Constructor
	 *
	 * @param string $key The default encryption key to use when none is specified.
	 */
	public function __construct($key)
	{

		ee()->load->helper('multibyte');

		$this->default_key = $key;

		$this->mb_available = extension_loaded('mbstring');

	}

	/**
	 * Genrates an initialization vector based on the encryption method
	 *
	 * @return string An initialization vector
	 */
	protected function generateInitializationVector()
	{
		return openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
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
	public function encrypt($string, $key = '')
	{
		$key = ($key) ?: $this->default_key;
		$iv = $this->generateInitializationVector();
		return $this->addNoise($iv.openssl_encrypt($string, $this->method, $key, $this->options, $iv), $key);
	}

	/**
	 * Takes an encrypted string and key and decrypts it.
	 *
	 * @param string $data The encrypted string
	 * @param string $key The secret key
	 * @return string|FALSE the decrypted string on success or FALSE on failure
	 */
	public function decrypt($data, $key = '')
	{
		$key = ($key) ?: $this->default_key;

		$data = $this->removeNoise($data, $key);

		$iv_size = openssl_cipher_iv_length($this->method);

		$iv = ee_mb_substr($data, 0, $iv_size, 'ascii');
		
		$data = ee_mb_substr($data, $iv_size, ee_mb_strlen($data, 'ascii'), 'ascii');

		return openssl_decrypt($data, $this->method, $key, $this->options, $iv);
	}

	/**
	 * Adds permuted noise to the IV + encrypted data to protect
	 * against Man-in-the-middle attacks on CBC mode ciphers
	 * http://www.ciphersbyritter.com/GLOSSARY.HTM#IV
	 *
	 * Function description
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	protected function addNoise($data, $key)
	{
		$keyhash = sha1($key);
		$keylen = ee_mb_strlen($keyhash, 'ascii');
		$str = '';
		$len = ee_mb_strlen($data, 'ascii');

        for ($i = 0, $j = 0, $len; $i < $len; ++$i, ++$j)
		{
			if ($j >= $keylen)
			{
				$j = 0;
			}

			$str .= chr((ord($data[$i]) + ord($keyhash[$j])) % 256);
		}

		return $str;
	}

	/**
	 * Removes permuted noise from the IV + encrypted data, reversing
	 * _add_cipher_noise()
	 *
	 * Function description
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	protected function removeNoise($data, $key)
	{
		$keyhash = sha1($key);
		$keylen = ee_mb_strlen($keyhash, 'ascii');
		$str = '';
		$len = ee_mb_strlen($data, 'ascii');

		for ($i = 0, $j = 0, $len; $i < $len; ++$i, ++$j)
		{
			if ($j >= $keylen)
			{
				$j = 0;
			}

			$temp = ord($data[$i]) - ord($keyhash[$j]);

			if ($temp < 0)
			{
				$temp = $temp + 256;
			}

			$str .= chr($temp);
		}

		return $str;
	}

	/**
	 * Encodes the string with the set encryption driver and then base64 encodes
	 * that.
	 *
	 * @param string $string The plaintext string
	 * @param string $key The encryption key, if not defined we'll use the default
	 * @return A base64 encoded string
	 */
	public function encode($string, $key = '')
	{
		return base64_encode($this->encrypt($string, $key));
	}

	/**
	 * Decodes an encoded string by first base64 decodeing it, then passing the
	 * string off to the driver for its decoding process.
	 *
	 * @param string $data A base64 encoded string
	 * @param string $key The encryption key, if not defined we'll use the default
	 * @return A plaintext strig
	 */
	public function decode($data, $key = '')
	{
		return $this->decrypt(base64_decode($data), $key);
	}

	/**
	 * Creates a signed hash value using hash_hmac()
	 *
	 * @throws InvalidArgumentException when the algorithm is invalid
	 * @param string $data	 Content to hash
	 * @param string $key The encryption key, if not defined we'll use the default
	 * @param string $algo Hashing algorithm, defaults to md5
	 * @return 	mixed   NULL if there is no data
	 * 	        		String consisting of the calculated message digest as lowercase hexits
	 */
	public function sign($data, $key = NULL, $algo = 'md5')
	{
		if (empty($data))
		{
			return NULL;
		}

		if ( ! in_array($algo, hash_algos()))
		{
			throw new InvalidArgumentException("{$algo} is not a valid hashing algorithm.");
		}

		$key = ($key) ?: $this->default_key;

		$token = hash_hmac($algo, $data, $key);

		return $token;
	}

	/**
	 * Verify the signed data hash
	 *
	 * @param string $data Current content
	 * @param string $signed_data Hashed content to compare to
	 * @param string $key The encryption key, if not defined we'll use the default
	 * @param string $algo hashing algorithm, defaults to md5
	 * @return 	mixed   NULL if there is no data
	 * 					FALSE if the signed data is not verified
	 * 	        		TRUE if the signed data is verified
	 */
	public function verifySignature($data, $signed_data, $key = NULL, $algo = 'md5')
	{
		if (empty($data))
		{
			return NULL;
		}

		 $new_sig = $this->sign($data, $key, $algo);

		// See PHP documentation not re timing attacks
		// http://php.net/manual/en/function.hash-hmac.php#111435
		if ($new_sig === $signed_data)
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Generates a random key to be used for anything, probably encryption
	 *
	 * @return string 32-character key
	 */
	public function generateKey()
	{
		return sha1(uniqid(random_int(-PHP_INT_MAX, PHP_INT_MAX), TRUE));
	}
}

// EOF
