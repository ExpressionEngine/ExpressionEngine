<?php

namespace EllisLab\ExpressionEngine\Protocol\Config;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author    EllisLab Dev Team
 * @copyright Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license   https://expressionengine.com/license
 * @link      https://ellislab.com
 * @since     Version 3.4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config Protocol
 *
 * @package    ExpressionEngine
 * @subpackage Core
 * @category   Core
 * @author     EllisLab Dev Team
 * @link       https://ellislab.com
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
