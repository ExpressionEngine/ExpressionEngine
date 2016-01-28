<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column\Scalar;

use EllisLab\ExpressionEngine\Service\Model\Column\StaticType;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Y/N Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class YesNo extends StaticType {

	/**
	 * Called when the user gets the column
	 */
	public static function get($data)
	{
		return static::isTruthy($data) ? TRUE : FALSE;
	}

	/**
	 * Called when the user sets the column
	 */
	public static function set($data)
	{
		return $data;
	}

	/**
	 * Called when the data is fetched from the db
	 */
	public static function load($db_data)
	{
		return $db_data;
	}

	/**
	 * Called when the data is stored in the db
	 */
	public static function store($data)
	{
		return static::isTruthy($data) ? 'y' : 'n';
	}

	/**
	 * Our ee-aware truthyness check
	 */
	protected static function isTruthy($data)
	{
		return ($data === TRUE || $data === 'y' || $data === 1);
	}
}

// EOF
