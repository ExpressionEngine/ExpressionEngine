<?php

namespace EllisLab\ExpressionEngine\Service\Addon;

use EllisLab\ExpressionEngine\Core\Application;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Addon Factory Class
 *
 * @package		ExpressionEngine
 * @subpackage	Filesystem
 * @category	Library
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Factory {

	protected $app;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Get the addon `$name`
	 *
	 * @param String $name Addon short name
	 * @return Object Addon The requested addon
	 */
	public function get($name)
	{
		$provider = $this->app->get($name);

		return new Addon($provider);
	}

	/**
	 * Get all addons
	 */
	public function all()
	{
		$providers = $this->app->getProviders();

		$all = array();

		foreach ($providers as $key => $obj)
		{
			$path = $obj->getPath();

			if (strpos($path, PATH_ADDONS) === 0 || strpos($path, PATH_THIRD) === 0)
			{
				$all[$key] = new Addon($obj);
			}
		}

		return $all;
	}
}
