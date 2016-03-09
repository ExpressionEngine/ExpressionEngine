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

		get_config(array('subclass_prefix' => 'Installer_'));

		parent::boot();
	}
}

// EOF
