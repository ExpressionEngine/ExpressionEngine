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

		if ( null === $encoding ) {

			$encoding = ee()->config->item('charset') ?: 'utf8';

		}

		// If standard encoding, strlen works just fine
		if ( ! in_array( strtolower($encoding), array( 'utf8', 'utf-8' ) ) ) {

			return strlen( $str );

		}

		// Check byte count
		$regex = '/(?:
			[\x00-\x7F]                  # single-byte sequences   0xxxxxxx
			| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
			| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
			| [\xE1-\xEC][\x80-\xBF]{2}
			| \xED[\x80-\x9F][\x80-\xBF]
			| [\xEE-\xEF][\x80-\xBF]{2}
			| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
			| [\xF1-\xF3][\x80-\xBF]{3}
			| \xF4[\x80-\x8F][\x80-\xBF]{2}
		)/x';

		// Start at 1 instead of 0 since the first thing we do is decrement.
		$count = 1;

		do {
			// We had some string left over from the last round, but we counted it in that last round.
			$count--;

			/*
			 * Split by UTF-8 character, limit to 1000 characters (last array element will contain
			 * the rest of the string).
			 */
			$pieces = preg_split( $regex, $str, 1000 );

			// Increment.
			$count += count( $pieces );

			// If there's anything left over, repeat the loop.
		} while ( $str = array_pop( $pieces ) );

		// Fencepost: preg_split() always returns one extra item in the array.
		return --$count;
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

