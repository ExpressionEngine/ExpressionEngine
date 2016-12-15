<?php
namespace EllisLab\ExpressionEngine\Service\Encrypt;

use EllisLab\ExpressionEngine\Service\Encrypt\Driver;

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
 * ExpressionEngine Encrypt Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Encrypt {

	private $driver;
	private $default_key;

	public function __construct(Driver $driver, $key)
	{
		$this->setDriver($driver);
		$this->default_key = $key;
	}

	public function setDriver(Driver $driver)
	{
		$this->driver = $driver;
	}

	public function getDriver()
	{
		return $this->driver;
	}

	public function encode($string, $key = '')
	{
		$key = ($key) ?: $this->default_key;
		return $this->driver->encode($string, $key);
	}

	public function decode($data, $key = '')
	{
		$key = ($key) ?: $this->default_key;
		return $this->driver->decode($data, $key);
	}

	/**
	 * Creates a signed hash value using hash_hmac()
	 *
	 * @param string $data	 Content to hash
	 * @param mixed	$key Secret key, defaults to DB username.password if empty
	 * @param string $algo hashing algorithm, defaults to md5
	 * @return 	mixed   NULL if there is no data
	 * 					FALSE if the hashing algorithm is unknown
	 * 	        		String consisting of the calculated message digest as lowercase hexits
	 *
	 */

	public function sign($data, $key = NULL, $algo = 'md5')
	{
		if (empty($data))
		{
			return NULL;
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
	 * @param mixed	$key Secret key
	 * @param string $algo hashing algorithm, defaults to md5
	 * @return 	mixed   NULL if there is no data
	 * 					FALSE if the signed data is not verified
	 * 	        		TRUE if the signed data is verified
	 *
	 */

	public function verify_signature($data, $signed_data, $key = NULL, $algo = 'md5')
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
	 * Generate an SHA1 Hash
	 */
	public function sha1($str)
	{
		if ( ! function_exists('sha1'))
		{
			return bin2hex(mhash(MHASH_SHA1, $str));
		}
		else
		{
			return sha1($str);
		}
	}

	/**
	 * Hash encode a string
	 */
	public function hash($str)
	{
		return $this->sha1($str);
	}

}

// EOF
