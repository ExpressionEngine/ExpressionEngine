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
	protected $cache;

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
	public function file($filename = 'config')
	{
		// Get the proper filename
		$fullpath = realpath($this->dirname.'/'.$filename.'.php');

		if (file_exists($fullpath))
		{
			// Cache the config File
			if ( ! isset($this->cache[$fullpath]))
			{
				$this->cache[$fullpath] = new File($fullpath);
			}

			return $this->cache[$fullpath];
		}

		throw new \Exception('No config file was found.');
	}
}
