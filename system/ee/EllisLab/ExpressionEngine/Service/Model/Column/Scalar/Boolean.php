<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Column\Scalar;

use EllisLab\ExpressionEngine\Service\Model\Column\StaticType;

/**
 * Model Service: Boolean Typed Column
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

// EOF
