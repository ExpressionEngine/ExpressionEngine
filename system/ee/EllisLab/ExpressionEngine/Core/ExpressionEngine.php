<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
