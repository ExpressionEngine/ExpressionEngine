<?php

namespace EllisLab\ExpressionEngine\Core;

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

		get_config(array('subclass_prefix' => 'Installer_'));

		parent::boot();
	}
}

// EOF
