<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

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

if ( ! function_exists( 'ee_get_encoding' ) )
{
	function ee_get_encoding($encoding)
	{

		if (null === $encoding) {

			return ee()->config->item('charset') ?: 'utf8';

		}

		if ('UTF-8' === $encoding) {

			return 'UTF-8';

		}

		$encoding = strtoupper($encoding);

		if ( '8BIT' === $encoding || 'BINARY' === $encoding ) {

			return 'CP850';

		}

		if ( 'UTF8' === $encoding ) {

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
if ( ! function_exists('ee_mb_strlen'))
{

	function ee_mb_strlen( $str, $encoding = null ) {

		$encoding = ee_get_encoding($encoding);

		if ('CP850' === $encoding || 'ASCII' === $encoding) {

			return strlen($str);

		}

		return @iconv_strlen($str, $encoding);

	}

}

if ( ! function_exists('ee_mb_strpos')) {

	function ee_mb_strpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		$encoding = ee_get_encoding($encoding);

		if ('CP850' === $encoding || 'ASCII' === $encoding) {

			return strpos($haystack, $needle, $offset);

		}

		$needle = (string) $needle;

		if ('' === $needle) {

			return false;

		}

		return iconv_strpos($haystack, $needle, $offset, $encoding);

	}

}

if( ! function_exists( 'ee_mb_substr ') ) {

	function ee_mb_substr($s, $start, $length = null, $encoding = null)
	{
		$encoding = ee_get_encoding($encoding);

		if ('CP850' === $encoding || 'ASCII' === $encoding) {

			return (string) substr($s, $start, null === $length ? 2147483647 : $length);


		}

		if ($start < 0) {

			$start = iconv_strlen($s, $encoding) + $start;

			if ($start < 0) {

				$start = 0;

			}

		}

		if (null === $length) {

			$length = 2147483647;

		}
		elseif ($length < 0)
		{

			$length = iconv_strlen($s, $encoding) + $length - $start;

			if ($length < 0) {

				return '';

			}

		}

		return (string) iconv_substr($s, $start, $length, $encoding);

	}

}

