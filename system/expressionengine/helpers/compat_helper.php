<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Compat Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

/**
 * Fixes a bug in PHP 5.2.9 where sort flags defaulted
 * to SORT_REGULAR, resulting in some very odd removal
 * behavior. And since we have now started using it,
 * we stupidly need to compat the whole thing. -pk
 */
function ee_array_unique(array $arr, $sort_flags = SORT_STRING)
{
	// 5.2.9 introduced both the flags and the bug
	if (is_php('5.2.9'))
	{
		return array_unique($arr, $sort_flags);
	}

	// before 5.2.9 sort_string was the default
	if ($sort_flags === SORT_STRING)
	{
		return array_unique($arr);
	}

	// no point uniquing an array of 1 or nil
	if (count($arr) < 2)
	{
		return $arr;
	}

	// to be in parity with php's solution,
	// we need to keep the original key positions
	$key_pos = array_flip(array_keys($arr));
	$ret_arr = $arr;

	asort($arr, $sort_flags);

	$last_kept = reset($arr);
	$last_kept_k = key($arr);

	next($arr); // skip ahead

	while (list($k, $v) = each($arr))
	{
		if ($sort_flags === SORT_NUMERIC)
		{
			$keep = (bool) ((float) $last_kept - (float) $v);
		}
		else // SORT_REGULAR
		{
			$keep = ($last_kept != $v);
		}

		if ($keep)
		{
			$last_kept = $v;
			$last_kept_k = $k;
		}
		else
		{
			// This is the mind boggling and in my opinion buggy part of
			// the algorithm php uses. We unset any duplicates that follow
			// the first value. Which is fine thinking narrowly about unique
			// array values.
			// However, it's extremely unintuitive when thinking about php
			// arrays, where duplicate keys override earlier keys.
			// It also makes this algorithm unstable, so that moving a boolean
			// true value to different spots in the array vastly changes the
			// output.
			$unset = $k;
			if ($key_pos[$last_kept_k] > $key_pos[$k])
			{
				$unset = $last_kept_k;

				$last_kept = $v;
				$last_kept_k = $k;
			}

			unset($arr[$unset]);
			unset($ret_arr[$unset]);
		}
	}

	unset($arr);
	return $ret_arr;
}

// ------------------------------------------------------------------------

/**
 * Based on the c implementation from the ISC
 */
function ee_inet_ntop($ip)
{
	// unpack the binary
	$hex = unpack('H*', $ip);
	$hex = current($hex);

	$len = strlen($hex);

	// ipv4
	if ($len == 8)
	{
		$parts = str_split($hex, 2);
		$parts = array_map('hexdec', $parts);
		return implode('.', $parts);
	}

	// ipv6
	if ($len != 32)
	{
		show_error('Invalid IP address.');
	}

	$parts = str_split($hex, 4);

	// find the longest run of zeros
	$start = -1;
	$len = 0;
	$best_start = -1;
	$best_len = 0;

	foreach ($parts as $i => &$part)
	{
		if ($part == '0000')
		{
			if ($start == -1)
			{
				$start = $i;
				$len = 0;
			}

			$len++;
		}
		elseif ($start != -1 && ($best_start == -1 OR $len > $best_len))
		{
			$best_start = $start;
			$best_len = $len;
			$start = -1;
		}
	}

	// didn't move best?
	if ($start != -1 && ($best_start == -1 OR $len > $best_len))
	{
		$best_start = $start;
		$best_len = $len;
	}

	// print out the result
	$out = '';

	foreach ($parts as $i => &$part)
	{
		if ($best_start != -1)
		{
			if ($i >= $best_start && $i < ($best_start + $best_len))
			{
				if ($i == $best_start)
				{
					$out .= ':';
				}
				continue;
			}
		}

		if ($i)
		{
			$out .= ':';
		}

		// ipv4 mapped?
		if ($i == 6 && $best_start == 0 &&
			($best_len == 6 OR ($best_len == 5 && $parts[5] == 'ffff')))
		{
			$out .= ee_inet_ntop(pack('H4H4', $parts[6], $parts[7]));
			break;
		}

		// collapse the hex string
		$out .= sprintf('%x', hexdec($part));
	}

	return $out;
}

// ------------------------------------------------------------------------

/**
 * Based loosely on the c implementation from the ISC
 */
function ee_inet_pton($str)
{
	$pad_off = -1;

	if (strpos($str, ':') !== FALSE)
	{
		$parts = explode(':', $str);

		foreach ($parts as $i => &$part)
		{
			if ($part === '' && $pad_off == -1)
			{
				$pad_off = $i;
				continue;
			}

			// ipv4 mapped?
			if (strpos($part, '.'))
			{
				if ($i + 1 != count($parts))
				{
					show_error('Invalid IP address.');
				}

				// convert the ipv4 one, unpack as hex, pad, and add
				// I'm sure there is a way to just concatenate the binary
				// but I can't figure it out right now
				$ipv4 = unpack('H*', ee_inet_pton($part));
				list($cur, $next) = str_split(current($ipv4), 4);

				$part = str_pad($cur, 4, '0', STR_PAD_LEFT);
				$parts[$i + 1] = str_pad($next, 4, '0', STR_PAD_LEFT);
				break;
			}

			$part = str_pad($part, 4, '0', STR_PAD_LEFT);
		}

		if ($pad_off != -1)
		{
			$pad_len = 9 - count($parts);
			$zeros = array_fill(0, $pad_len, '0000');
			array_splice($parts, $pad_off, 1, $zeros);
		}

		$args = $parts;
		array_unshift($args, str_repeat('H4', 8));
		return call_user_func_array('pack', $args);
	}

	if (strpos($str, '.'))
	{
		return pack('N', ip2long($str));
	}

	show_error('Invalid IP address.');
}

// ------------------------------------------------------------------------

/**
 * Some windows servers don't have the inet_* functions
 */
if ( ! function_exists('inet_ntop'))
{
	function inet_ntop($in)
	{
		return ee_inet_ntop($in);
	}
}

if ( ! function_exists('inet_pton'))
{
	function inet_pton($in)
	{
		return ee_inet_pton($in);
	}
}


/* End of file  */
/* Location: ./system/expressionengine/helpers/compat_helper.php */