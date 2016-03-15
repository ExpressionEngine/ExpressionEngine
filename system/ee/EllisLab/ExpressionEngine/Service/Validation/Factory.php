<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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

// EOF
