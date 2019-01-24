<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Config;

use EllisLab\ExpressionEngine\Protocol\Config\Config;

/**
 * Config With Defaults
 */
abstract class ConfigWithDefaults implements Config {

	/**
	 * @var the default values to check when referencing this config
	 */
	protected $defaults = array();

	/**
	 * Get the default for a given config item. If they gave us a
	 * default, we prefer that over the default default.
	 *
	 * @param string $item The config item to pull
	 * @param mixed $prefer_default The default to use instead of the value from
	 *   $this->defaults
	 * @return mixed The value stored in this config
	 */
	protected function getDefaultFor($item, $prefer_default = NULL)
	{
		if ($item == '')
		{
			return $this->defaults;
		}

		if (isset($prefer_default))
		{
			return $prefer_default;
		}

		if (array_key_exists($item, $this->defaults))
		{
			return $this->defaults[$item];
		}

		return $prefer_default;
	}

}
