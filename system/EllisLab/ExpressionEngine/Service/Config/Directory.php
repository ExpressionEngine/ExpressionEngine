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

	protected $basepath;
	protected $cache;

	function __construct($basepath)
	{
		if ( ! is_array($basepath))
		{
			$basepath = array($basepath);
		}

		$this->basepath = $basepath;
	}

	public function file($name)
	{
		foreach ($this->basepath as $basepath)
		{
			if (file_exists($basepath.$name.'.php'))
			{
				// Cache the config File
				if ( ! isset($cache[$basepath.$name]))
				{
					$cache[$basepath.$name] = $this->createFile($basepath.$name.'.php');
				}

				return $cache[$basepath.$name];
			}
		}
	}

	protected function createFile($filename)
	{
		return new File($filename);
	}
}
