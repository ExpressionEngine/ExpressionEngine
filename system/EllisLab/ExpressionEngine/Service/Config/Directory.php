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
 * ExpressionEngine Config Directory Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Directory {

	protected $dirname;
	protected $cache;

	function __construct($dirname)
	{
		$this->dirname = $dirname;
	}

	public function file($basename)
	{
		// Get the proper filename
		$fullpath = realpath($this->dirname.'/'.$basename.'.php');

		if (file_exists($fullpath))
		{
			// Cache the config File
			if ( ! isset($cache[$fullpath]))
			{
				$cache[$fullpath] = static::createFile($fullpath);
			}

			return $cache[$fullpath];
		}

		throw new \Exception('No config file was found.');
	}

	protected static function createFile($fullpath)
	{
		return new File($fullpath);
	}
}
