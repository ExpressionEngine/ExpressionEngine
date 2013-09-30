<?php

/**
 * Really basic autoloader using the PSR-4 proposal autoloading rules.
 *
 * I think that makes more sense in a namespaced application than PSR-0. Those
 * underscore rules seem to just be dead weight from the pre-5.3 days.
 */
class Autoloader {

	protected $prefixes = array();


	public function __construct()
	{
		$this->prefixes['EllisLab'] = APPPATH . '../EllisLab/';
	}

	/**
	 * Register the autoloader with PHP
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'loadClass'));
	}

	/**
	 * Remove the autoloader
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'loadClass'));
	}

	/**
	 * Map a namespace prefix to a path
	 */
	public function addPrefix($namespace, $path)
	{
		$this->prefixes[$namespace] = $path;
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
		$base_dir = APPPATH;

		// @todo this prefix handling will not do sub-namespaces correctly
		foreach ($this->prefixes as $prefix => $path)
		{
			if (strpos($class, $prefix) === 0)
			{
				// From inside to out: Strip off the prefix from the namespace, turn the namespace into 
				// a path, prepend the path prefix, append .php.  
				$class_path = $path . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

				require $class_path;
				return;
			}
		}
	}
}
