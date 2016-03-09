<?php

namespace EllisLab\ExpressionEngine\Service\Addon;

use EllisLab\ExpressionEngine\Core\Application;
use EllisLab\ExpressionEngine\Core\Provider;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Factory {

	/**
	 * @var Application object
	 */
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
		if ( ! $this->app->has($name))
		{
			return NULL;
		}

		$provider = $this->app->get($name);

		if ($this->isAddon($provider))
		{
			return new Addon($provider);
		}

		return NULL;
	}

	/**
	 * Get all addons
	 *
	 * @return array An array of Addon objects.
	 */
	public function all()
	{
		$providers = $this->app->getProviders();

		$all = array();

		foreach ($providers as $key => $obj)
		{
			if ($this->isAddon($obj))
			{
				$all[$key] = new Addon($obj);
			}
		}

		return $all;
	}

	/**
	 * Fetch all installed addons
	 *
	 * @return array An array of Addon objects.
	 */
	public function installed()
	{
		return array_filter($this->all(), function($addon)
		{
			return $addon->isInstalled();
		});
	}

	/**
	 * Is a given provider an addon?
	 *
	 * @return bool Is an addon?
	 */
	protected function isAddon(Provider $provider)
	{
		$path = $provider->getPath();

		return (strpos($path, PATH_ADDONS) === 0 || strpos($path, PATH_THIRD) === 0);
	}
}

// EOF
