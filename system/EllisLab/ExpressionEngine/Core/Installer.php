<?php

namespace EllisLab\ExpressionEngine\Core;

class Installer extends Core {
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

	/**
	 * Retrieve the config path for this core
	 * @return string Config path
	 */
	protected function getConfigPath()
	{
		return SYSPATH.'installer/config';
	}
}
