<?php if( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Multibyte Helpers
 */

/**
 * gets encoding by string
 * @param  string $encoding
 * @return string
 */
if( ! function_exists( 'ee_get_encoding' ) )
{

	function ee_get_encoding($encoding)
	{

		if($encoding === null)
		{

			return ee()->config->item('charset') ?: 'utf8';

		}

		if($encoding === 'UTF-8')
		{

			return 'UTF-8';

		}

		$encoding = strtoupper($encoding);

		if($encoding ===  '8BIT' || $encoding === 'BINARY' )
		{

			return 'CP850';

		}

		if($encoding ===  'UTF8')
		{

			return 'UTF-8';

		}

		return $encoding;

	}

}

/**
 * Replace deprecated mb_string
 * @param  string $str
 * @param  string $encoding
 * @return integer
 */
if( ! function_exists('ee_mb_strlen'))
{

	function ee_mb_strlen( $str, $encoding = null )
	{
		if (function_exists('mb_strlen')) {
			if (!empty($encoding)) {
				return mb_strlen($str, $encoding);
			} else {
				return mb_strlen($str);
			}
		}

		$encoding = ee_get_encoding($encoding);

		if($encoding === 'CP850' || $encoding === 'ASCII' || ! extension_loaded('iconv'))
		{

			return strlen($str);

		}

		return @iconv_strlen($str, $encoding);

	}

}

/**
 * Replace deprecated mb_strpos
 * @param  string  $haystack
 * @param  string  $needle
 * @param  integer $offset
 * @param  string  $encoding
 * @return mixed - FALSE if not found, integer if found
 */
if( ! function_exists('ee_mb_strpos'))
{

	function ee_mb_strpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		if (function_exists('mb_strpos')) {
			if (!empty($encoding)) {
				return mb_strpos($haystack, $needle, $offset, $encoding);
			} else {
				return mb_strpos($haystack, $needle, $offset);
			}
		}

		$encoding = ee_get_encoding($encoding);

		if($encoding === 'CP850' || $encoding === 'ASCII' || ! extension_loaded('iconv'))
		{

			return strpos($haystack, $needle, $offset);

		}

		$needle = (string) $needle;

		if('' === $needle)
		{

			return false;

		}

		return iconv_strpos($haystack, $needle, $offset, $encoding);

	}

}

/**
 * Replace deprecated mb_substr
 * @param  string $s
 * @param  string $start
 * @param  mixed $length
 * @param  mixed $encoding
 * @return string
 */
if( ! function_exists( 'ee_mb_substr ') )
{

	function ee_mb_substr($str, $start, $length = null, $encoding = null)
	{
		if (function_exists('mb_substr')) {
			if (!empty($encoding)) {
				return mb_substr($str, $start, $length, $encoding);
			} else {
				return mb_substr($str, $start, $length);
			}
		}

		$encoding = ee_get_encoding($encoding);

		if('CP850' === $encoding || 'ASCII' === $encoding || ! extension_loaded('iconv'))
		{

			return (string) substr($str, $start, null === $length ? 2147483647 : $length);


		}

		if($start < 0)
		{

			$start = iconv_strlen($str, $encoding) + $start;

			if($start < 0)
			{

				$start = 0;

			}

		}

		if(null === $length)
		{

			$length = 2147483647;

		}
		elseif($length < 0)
		{

			$length = iconv_strlen($str, $encoding) + $length - $start;

			if($length < 0)
			{

				return '';

			}

		}

		return (string) iconv_substr($str, $start, $length, $encoding);

	}

}

