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
 * ExpressionEngine ExclusiveOr Driver Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ExclusiveOr implements Driver {

	/**
	 * @var Obj $hash_object An object which has a `hash()` method
	 */
	protected $hash_object;

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
	 * Takes a plain-text string and key as input and generates an
	 * encoded bit-string using XOR
	 *
	 * @param string $string The plaintext string
	 * @param string $key The secret key
	 * @return string An XOR encoded string
	 */
	public function encode($string, $key)
	{
		$rand = '';
		while (strlen($rand) < 32)
		{
			$rand .= mt_rand(0, mt_getrandmax());
		}

		$rand = $this->hash_object->hash($rand);

		$enc = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
		}

		return $this->merge($enc, $key);
	}

	/**
	 * Takes an encoded string and key as input and generates the
	 * plain-text original message
	 *
	 * @param string $data An XOR encoded string
	 * @param string $key The secret key
	 * @return string The plaintext string
	 */
	public function decode($data, $key)
	{
		$string = $this->merge($data, $key);

		$dec = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
		}

		return $dec;
	}

	/**
	 * XOR key + string Combiner
	 *
	 * Takes a string and key as input and computes the difference using XOR
	 *
	 * @param string $string A string to merge
	 * @param string $key The secret key
	 * @return string An XOR encoded string
	 */
	protected function merge($string, $key)
	{
		$hash = $this->hash_object->hash($key);
		$str = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
		}

		return $str;
	}

}

// EOF
