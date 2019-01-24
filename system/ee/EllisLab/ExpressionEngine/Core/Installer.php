<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Core;

/**
 * Core Installer
 */
class Installer extends Core {
	/**
	 *
	 */
	public function boot()
	{
		define('APPPATH', SYSPATH.'ee/installer/');
		define('EE_APPPATH', BASEPATH);

		define('PATH_ADDONS', SYSPATH .'ee/EllisLab/Addons/');
		define('PATH_MOD', SYSPATH .'ee/EllisLab/Addons/');
		define('PATH_PI', SYSPATH .'ee/EllisLab/Addons/');
		define('PATH_EXT', SYSPATH .'ee/EllisLab/Addons/');
		define('PATH_FT', SYSPATH .'ee/EllisLab/Addons/');
		define('PATH_RTE', EE_APPPATH.'rte_tools/');
		define('INSTALLER', TRUE);

		get_config(array('subclass_prefix' => 'Installer_'));

		parent::boot();
	}
}

// EOF
