<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Protocol\Config;

/**
 * ExpressionEngine Config Protocol interface
 */
interface Config {

	/**
	 * Get a config item
	 *
	 * @param string $key Config key name
	 * @param mixed $default Default value to return if item does not exist.
	 * @return mixed
	 */
	public function get($key, $default = NULL);
}
