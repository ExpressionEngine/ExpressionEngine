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

	public function encode($string, $key)
	{
		$rand = '';
		while (strlen($rand) < 32)
		{
			$rand .= mt_rand(0, mt_getrandmax());
		}

		$rand = ee('Encrypt')->hash($rand);

		$enc = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
		}

		return $this->merge($enc, $key);
	}

	public function decode($data, $key)
	{
		$string = $this->merge($string, $key);

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
	 */
	protected function merge($string, $key)
	{
		$hash = ee('Encrypt')->hash($key);
		$str = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
		}

		return $str;
	}

}

// EOF
