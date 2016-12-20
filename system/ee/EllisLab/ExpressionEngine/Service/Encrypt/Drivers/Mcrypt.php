<?php
namespace EllisLab\ExpressionEngine\Service\Encrypt\Drivers;

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
 * ExpressionEngine Mcrypt Driver Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Mcrypt implements Driver {

	/**
	 * @var string $cipher The encryption cipher that will be used.
	 */
	protected $cipher = MCRYPT_RIJNDAEL_256;

	/**
	 * @var string $mode The mode mcrypt will use
	 */
	protected $mode = MCRYPT_MODE_CBC;

	/**
	 * @var bool $mb_available Do we have the mbstring extension?
	 */
	protected $mb_available;

	/**
	 * @var Obj $hash_object An object which has a `hash()` method
	 */
	protected $hash_object;

	/**
	 * Constructor; sets the md_available property
	 */
	public function __construct()
	{
		$this->mb_available = (MB_ENABLED) ?: extension_loaded('mbstring');
	}

	/**
	 * Sets the hash object
	 *
	 * @param obj $obj An object which has a `hash()` method
	 * @return self This returns a reference to itself
	 */
	public function setHashObject($obj)
	{
		$this->hash_object = $obj;
		return $this;
	}

	/**
	 * Mcrypt needs keys of certain lengths. If the key isn't already of the
	 * correct length we'll md5 hash the key, which will result in a key of the
	 * propery length
	 *
	 * @param string $key The secret key
	 * @return string The secret key
	 */
	protected function ensureKeySize($key)
	{
		if ( ! in_array(strlen($key), array(16, 24, 32)))
		{
			$key = md5($key);
		}

		return $key;
	}

	/**
	 * Takes a plain-text string and key as input and generates an
	 * encrypted string
	 *
	 * @param string $string The plaintext string
	 * @param string $key The secret key
	 * @return string A mcrypt encrypted string
	 */
	public function encode($string, $key)
	{
		$key = $this->ensureKeySize($key);
		$init_size = mcrypt_get_iv_size($this->cipher, $this->mode);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		return $this->add_cipher_noise($init_vect.mcrypt_encrypt($this->cipher, $key, $string, $this->mode, $init_vect), $key);
	}

	/**
	 * Takes an encoded string and key as input and generates the
	 * plain-text original message
	 *
	 * @param string $data A mcrypt encrypted string
	 * @param string $key The secret key
	 * @return string|FALSE The plaintext string on success, or FALSE on failure
	 */
	public function decode($data, $key)
	{
		$key = $this->ensureKeySize($key);
		$data = $this->remove_cipher_noise($data, $key);
		$init_size = mcrypt_get_iv_size($this->cipher, $this->mode);
		$mb_adjusted_data_length =  ($this->mb_available) ? mb_strlen($data, 'ascii') : strlen($data);

		if ($init_size > $mb_adjusted_data_length)
		{
			return FALSE;
		}

		$init_vect = ($this->mb_available) ? mb_substr($data, 0, $init_size, 'ascii') : substr($data, 0, $init_size);
		$data = ($this->mb_available) ? mb_substr($data, $init_size, mb_strlen($data, 'ascii'), 'ascii') : substr($data, $init_size);
		return rtrim(mcrypt_decrypt($this->cipher, $key, $data, $this->mode, $init_vect), "\0");
	}

	/**
	 * Adds permuted noise to the IV + encrypted data to protect
	 * against Man-in-the-middle attacks on CBC mode ciphers
	 * http://www.ciphersbyritter.com/GLOSSARY.HTM#IV
	 *
	 * @param string $data A mcrypt encrypted string
	 * @param string $key The secret key
	 * @return string A mcrypt encrypted string with noise
	 */
	protected function add_cipher_noise($data, $key)
	{
		$keyhash = $this->hash_object->hash($key);
		$keylen = ($this->mb_available) ? mb_strlen($keyhash, 'ascii') : strlen($keyhash);
		$str = '';
		$len = ($this->mb_available) ? mb_strlen($data, 'ascii') : strlen($data);

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
	 * add_cipher_noise()
	 *
	 * @param string $data A mcrypt encrypted string with noise
	 * @param string $key The secret key
	 * @return string A mycrypt encrypted string
	 */
	protected function remove_cipher_noise($data, $key)
	{
		$keyhash = $this->hash_object->hash($key);
		$keylen = ($this->mb_available) ? mb_strlen($keyhash, 'ascii') : strlen($keyhash);
		$str = '';
		$len = ($this->mb_available) ? mb_strlen($data, 'ascii') : strlen($data);

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

}

// EOF
