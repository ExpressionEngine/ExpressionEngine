<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @link		http://expressionengine.com
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

/* End of file  */
/* Location: ./system/expressionengine/helpers/compat_helper.php */