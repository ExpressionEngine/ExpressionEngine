<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Column;

/**
 * Model Service Primitive Typed Column
 */
abstract class StaticType implements Type {

	protected static $instances = array();

	/**
	 * For these basic types, we don't want to spin up more than one
	 * object, but we also don't want children to have to manage their
	 * instances, so this solves both those problems. I'm not sold on it.
	 */
	public static function create()
	{
		$class = get_called_class();

		if ( ! isset(static::$instances[$class]))
		{
			static::$instances[$class] = new static;
		}

		return static::$instances[$class];
	}

	public static function load($db_data)
	{
		return $db_data;
	}

	public static function store($data)
	{
		return $data;
	}

	public static function get($data)
	{
		return $data;
	}

	public static function set($data)
	{
		return $data;
	}
}

// EOF
