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

/**
 * Replace deprecated mb_string
 * @param  string $str
 * @param  string $encoding
 * @return integer
 */
if ( ! function_exists('ee_mb_string'))
{

	function ee_mb_string( $str, $encoding = null ) {

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