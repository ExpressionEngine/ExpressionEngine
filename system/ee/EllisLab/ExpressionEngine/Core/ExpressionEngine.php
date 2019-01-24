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
 * Core\ExpressionEngine
 */
class ExpressionEngine extends Core {

	/**
	 *
	 */
	public function boot()
	{
		define('APPPATH', BASEPATH);
		define('INSTALLER', FALSE);

		get_config(array('subclass_prefix' => 'EE_'));

		parent::boot();
	}

}
