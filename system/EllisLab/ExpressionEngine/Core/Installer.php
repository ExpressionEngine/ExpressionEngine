<?php

namespace EllisLab\ExpressionEngine\Core;

class Installer extends Core {

	protected $configPath = SYSPATH.'installer/config';

	/**
	 *
	 */
	public function boot()
	{
		define('APPPATH', SYSPATH.'installer/');
		define('EE_APPPATH', BASEPATH);

		get_config(array('subclass_prefix' => 'Installer_'));

		parent::boot();
	}


}
