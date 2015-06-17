<?php

namespace EllisLab\ExpressionEngine\Core;

class ExpressionEngine extends Core {

	/**
	 *
	 */
	public function boot()
	{
		define('APPPATH', BASEPATH);

		get_config(array('subclass_prefix' => 'EE_'));

		parent::boot();
	}

}