<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column\Scalar;

use EllisLab\ExpressionEngine\Service\Model\Column\StaticType;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Model Boolean Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Boolean extends StaticType {

	/**
	 * Called when the user gets the column
	 */
	public static function get($data)
	{
		return static::boolval($data);
	}

	/**
	 * Called when the user sets the column
	 */
	public static function set($data)
	{
		return $data;
	}

	/**
	 * Called when the column is fetched from db
	 */
	public static function load($db_data)
	{
		return static::boolval($db_data);
	}

	/**
	 * Called before the column is written to the db
	 */
	public static function store($data)
	{
		return static::boolval($data);
	}


	private static function boolval($data)
	{
		return is_scalar($data) ? (bool) $data : FALSE;
	}
}
