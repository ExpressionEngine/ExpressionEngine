<?php

namespace EllisLab\ExpressionEngine\Service\Config;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author    EllisLab Dev Team
 * @copyright Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license   http://ellislab.com/expressionengine/user-guide/license.html
 * @link      http://ellislab.com
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
 * @link       http://ellislab.com
 */
class File
{
	protected $config;
	protected $defaults = array(
		'database' => array(
			'active_group'     => 'expressionengine',
			'active_record'    => TRUE,
			'expressionengine' => array(
				'hostname' => 'localhost',
				'username' => 'root',
				'password' => '',
				'database' => '',
				'dbdriver' => 'mysql',
				'pconnect' => FALSE,
				'dbprefix' => 'exp_',
				'swap_pre' => 'exp_',
				'db_debug' => TRUE,
				'cache_on' => FALSE,
				'autoinit' => FALSE,
				'char_set' => 'utf8',
				'dbcollat' => 'utf8_general_ci',
				'cachedir' => '', // Set in constructor
				'stricton' => TRUE,
			)
		)
	);

	/**
	 * Create a new Config\File object, will merge with defaults
	 * @param string $path Full path to the config file
	 */
	function __construct($path)
	{
		$this->defaults['database']['expressionengine']['cachedir'] = rtrim(APPPATH, '/').'/cache/db_cache/';

		require($path);
		$this->config = array_replace_recursive($this->defaults, $config);
	}

	/**
	 * Get an item from the config, you can use
	 * "item.subitem.subsubitem" to drill down in the config
	 * @param  string $item    The config item to get
	 * @param  mixed  $default The value to return if $item can not be found
	 * @return mixed           The value found for $item, otherwise $default
	 */
	public function get($name, $default = NULL)
	{
		$config = $this->findConfig($name);
		return ($config !== NULL) ? $config : $default;
	}

	/**
	 * Set an item in the config. You can use 'item.subitem.subsubitem' to drill
	 * down in the config.
	 * @param  string $item    The config item to set
	 * @param  mixed  $value   The value to set
	 * @return void
	 */
	public function set($name, $value)
	{
		$config = &$this->findConfig($name, function($key, $config) {
			$config[$key] = '';
		});
		$config = $value;
	}

	/**
	 * Find the config item in the config array
	 * @param  string $name     The config item to find
	 * @param  callable $callback What to call if we don't find an item in the
	 *                            config array, defaults to returning null
	 * @return mixed            Will return a reference to the item in the
	 *                          config array if it exists, otherwise the
	 *                          callback() will be called
	 */
	private function &findConfig($name, $callback = '')
	{
		// Set a default callback
		if ( ! is_callable($callback))
		{
			$callback = function() {
				return NULL;
			};
		}

		$config = $this->config;

		foreach (explode('.', $name) as $key)
		{
			// If what we're looking for doesn't exist, return the default
			if ( ! array_key_exists($key, $config))
			{
				return $callback($key, $config);
			}

			$config = $config[$key];
		}

		return $config;
	}
}
