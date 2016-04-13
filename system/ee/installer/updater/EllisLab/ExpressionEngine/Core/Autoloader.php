<?php

namespace EllisLab\ExpressionEngine\Core;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.9.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Autoloader
 *
 * Really basic autoloader using the PSR-4 autoloading rules.
 *
 * I think that makes more sense in a namespaced application than PSR-0. Those
 * underscore rules seem to just be dead weight from the pre-5.3 days.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Autoloader {

	protected $prefixes = array();

	protected static $instance;

	/**
	 * Use as a singleton
	 */
	public static function getInstance()
	{
		if ( ! isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Register the autoloader with PHP
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'loadClass'));

		return $this;
	}

	/**
	 * Remove the autoloader
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'loadClass'));

		return $this;
	}

	/**
	 * Map a namespace prefix to a path
	 */
	public function addPrefix($namespace, $path)
	{
		$this->prefixes[$namespace] = $path;

		return $this;
	}

	/**
	 * Handle the autoload call.
	 *
	 * @param String $class Fully qualified class name. As of 5.3.3 this does
	 *                      not include a leading slash.
	 * @return void
	 */
	public function loadClass($class)
	{
		// @todo this prefix handling will not do sub-namespaces correctly
		foreach ($this->prefixes as $prefix => $path)
		{
			if (strpos($class, $prefix) === 0)
			{
				// From inside to out: Strip off the prefix from the namespace, turn the namespace into
				// a path, prepend the path prefix, append .php.
				$class_path = $path . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

				if (file_exists($class_path))
				{
					require_once $class_path;
					return;
				}
			}
		}

		// Keep this commented out until we're fully namespaced. PHP will handle it.
		//throw new \RuntimeException('Failed to load class: ' . $class . '!');
	}
}

// EOF
