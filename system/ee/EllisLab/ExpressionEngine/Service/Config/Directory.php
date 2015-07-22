<?php

namespace EllisLab\ExpressionEngine\Service\Config;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package   ExpressionEngine
 * @author    EllisLab Dev Team
 * @copyright Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license   https://ellislab.com/expressionengine/user-guide/license.html
 * @link      http://ellislab.com
 * @since     Version 3.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config Directory Class
 *
 * @package    ExpressionEngine
 * @subpackage Core
 * @category   Core
 * @author     EllisLab Dev Team
 * @link       http://ellislab.com
 */
class Directory {

	protected $dirname;

	protected $cache = array();

	/**
	 * Create a new Config\Directory object
	 *
	 * @param string $dirname Path to the directory, can be relative
	 */
	function __construct($dirname)
	{
		$this->dirname = realpath($dirname);
	}

	/**
	 * Returns a Config\File class representing the config file
	 *
	 * @param  string $filename name of the file
	 * @return File             Config\File object
	 * @throws Exception If no config file is found
	 */
	public function getFile($filename = 'config')
	{
		if (array_key_exists($filename, $this->cache))
		{
			return $this->cache[$filename];
		}

		if ( ! $this->hasFile($filename))
		{
			throw new \Exception('No config file was found.');
		}

		$obj = new File($this->getPath($filename));

		return $this->cache[$filename] = $obj;
	}

	/**
	 * Check if this directory contains a given config file
	 */
	public function hasFile($filename)
	{
		return file_exists($this->getPath($filename));
	}

	/**
	 * Get a config item from this directory
	 */
	public function get($item, $default = NULL)
	{
		list($file, $item) = $this->getFileFor($item);

		return $file->get($item, $default);
	}

	/**
	 * Turn a filename into a full path
	 */
	protected function getPath($filename)
	{
		return realpath($this->dirname.'/'.$filename.'.php');
	}

	/**
	 * Given an item, figure out what file it resides in
	 *
	 * A config item with a period, such as: `lang.primary` can reside
	 * in one of two places:
	 *
	 * In the main config file as `$config['lang']['primary']`
	 * or
	 * In a `lang` config file as `$config['primary']`
	 *
	 * The nice thing about this split is that it means that items can be
	 * moved to a separate config file when an array gets too unwieldy,
	 * without requiring large refactorings in the calling code.
	 *
	 * @param String $item The config item to fetch
	 * @return String $filename A config file name (no path or ".php")
	 */
	protected function getFileFor($item)
	{
		$default = array($this->getFile(), $item);

		if (strpos($item, '.') === FALSE || $this->getFile()->has($item))
		{
			return $default;
		}

		list($filename, $item) = explode('.', $item, 2);

		if ($this->hasFile($filename))
		{
			$file = $this->getFile($filename);

			if ($file->has($item))
			{
				return array($file, $item);
			}
		}

		return $default;
	}
}
