<?php

namespace EllisLab\ExpressionEngine\Service\Config;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config File Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class File
{
	protected $config;
	protected $defaults = array();

	function __construct($path)
	{
		require($path);

		$this->config = $config;
	}

	public function get($name, $default = NULL)
	{
		if (array_key_exists($name, $this->config))
		{
			return $this->config[$name];
		}
		else if (array_key_exists($name, $this->defaults))
		{
			return $this->defaults[$name];
		}

		return $default;
	}
}
