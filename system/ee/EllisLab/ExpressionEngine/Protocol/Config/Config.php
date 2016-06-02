<?php

namespace EllisLab\ExpressionEngine\Protocol\Config;

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
