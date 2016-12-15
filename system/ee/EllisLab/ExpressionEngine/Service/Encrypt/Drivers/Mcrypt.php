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

	protected $cipher = MCRYPT_RIJNDAEL_256;
	protected $mode = MCRYPT_MODE_CBC;
	protected $mb_available;
	protected $hash_object;

	public function __construct()
	{
		$this->mb_available = (MB_ENABLED) ?: extension_loaded('mbstring');
	}

	public function setHashObject($obj)
	{
		$this->hash_object = $obj;
		return $this;
	}

	public function encode($string, $key)
	{
		$init_size = mcrypt_get_iv_size($this->cipher, $this->mode);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		return $this->add_cipher_noise($init_vect.mcrypt_encrypt($this->cipher, $key, $data, $this->mode, $init_vect), $key);
	}

	public function decode($data, $key)
	{
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
