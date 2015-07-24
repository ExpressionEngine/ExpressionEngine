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
}
