<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

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
 * ExpressionEngine Model Primitive Typed Column
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
