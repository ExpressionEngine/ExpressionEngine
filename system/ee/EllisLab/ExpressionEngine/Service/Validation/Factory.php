<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

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
 * ExpressionEngine Validator Factory
 *
 * @package		ExpressionEngine
 * @subpackage	Validation
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Factory {

	/**
	 *
	 */
	public function make($rules = array())
	{
		return new Validator($rules);
	}

}
