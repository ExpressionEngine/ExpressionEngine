<?php

namespace EllisLab\ExpressionEngine\Service\Config;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author    EllisLab Dev Team
 * @copyright Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license   https://expressionengine.com/license
 * @link      https://ellislab.com
 * @since     Version 3.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config File Class
 *
 * @package    ExpressionEngine
 * @subpackage Core
 * @category   Core
 * @author     EllisLab Dev Team
 * @link       https://ellislab.com
 */
class File extends ConfigWithDefaults {

	protected $config   = array();

	/**
	 * Create a new Config\File object
	 *
	 * @param string $path Full path to the config file
	 */
	function __construct($path)
	{
		// Load in config
		require($path);

		if (isset($config))
		{
			$this->config = $config;
		}

		$this->defaults = default_config_items();
	}

	/**
	 * Get an item from the config, you can use
	 * "item.subitem.subsubitem" to drill down in the config
	 *
	 * @param  string $path      The config item to get
	 * @param  mixed  $default   The value to return if $path can not be found
	 * @param  mixed  $raw_value Whether or not to return the raw value with unparsed variables
	 * @return mixed             The value found for $path, otherwise $default
	 */
	public function get($path, $default = NULL, $raw_value = FALSE)
	{
		$config = $this->getArrayValue($this->config, $path);

		if ( ! $raw_value)
		{
			$config = parse_config_variables($config);
		}

		return ($config !== NULL) ? $config : $default;
	}

	/**
	 * Check if the file has a given item
	 *
	 * @return bool TRUE if it has the item, FALSE if not
	 */
	public function has($path)
	{
		$config = $this->getArrayValue($this->config, $path);

		return ! is_null($config);
	}

	/**
	 * Get a config item as a boolean
	 *
	 * This is aware of some of EE's conventions, so it will
	 * cast strings y and n to the correct boolean.
	 *
	 * @param string $path    The config item to get
	 * @param bool   $default The default value
	 * @return bool  The value cast to bool
	 */
	public function getBoolean($path, $default = FALSE)
	{
		$value = $this->get($path, $default);

		if (is_bool($value))
		{
			return $value;
		}

		switch(strtolower($value))
		{
			case 'yes':
			case 'y':
			case 'on':
				return TRUE;
			break;

			case 'no':
			case 'n':
			case 'off':
				return FALSE;
			break;

			default:
				return NULL;
			break;
		}
	}

	/**
	 * Set an item in the config. You can use 'item.subitem.subsubitem' to drill
	 * down in the config.
	 *
	 * @param  string $path    The config item to set
	 * @param  mixed  $value   The value to set
	 * @return void
	 */
	public function set($path, $value)
	{
		$this->setArrayValue($this->config, $path, $value);
	}

	/**
	 * Get a nested array value given a dot-separated path
	 *
	 * @param  array  $array Array to traverse
	 * @param  string $path  Dot-separated path
	 * @return mixed         Array value
	 */
	private function getArrayValue($array, $path)
	{
		$path = explode('.', $path);

		for ($i = $array; $key = array_shift($path); $i = $i[$key])
		{
			if ( ! isset($i[$key]))
			{
				return NULL;
			}
		}

		return $i;
	}

	/**
	 * Set a nested array value given a dot-separated path
	 *
	 * @param array &$array  Array to traverse and set value in
	 * @param string $path   Dot-separated path
	 * @param mixed  $value  Value to set, pass in NULL to unset
	 */
	private function setArrayValue(&$array, $path, $value)
	{
		$path = explode('.', $path);

		for ($i = &$array; $key = array_shift($path); $i = &$i[$key])
		{
			if ( ! isset($i[$key]))
			{
				$i[$key] = array();
			}

			// Maintain a list of the last array and key for unsetting
			$last_array = &$i;
			$last_key = $key;
		}

		// Unset it if value is NULL
		if ($value === NULL)
		{
			unset($last_array[$last_key]);
		}
		else
		{
			$i = $value;
		}
	}
}

// EOF
