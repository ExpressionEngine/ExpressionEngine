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
				'cachedir' => "{APPPATH}/cache/db_cache/",
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
		// If passed a key with dots in it, we need to drill down
		if (stripos($name, '.') !== FALSE)
		{
			$config = $this->config;

			foreach (explode('.', $name) as $key)
			{
				// If what we're looking for doesn't exist, return the default
				if ( ! array_key_exists($key, $config))
				{
					return $default;
				}

				$config = $config[$key];
			}

			return $config ?: $default;
		}
		else if (array_key_exists($name, $this->config))
		{
			return $this->config[$name];
		}

		return $default;
	}
}
