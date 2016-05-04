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

	/**
	 * Check to see if a value passes a rule's validation
	 *
	 * @param  string $rule The rule to check
	 * @param  string $value The value to check
	 * @return boolean TRUE if the check passes
	 */
	public function check($rule, $value)
	{
		return $this->make(array('check' => $rule))
			->validate(array('check' => $value))
			->isValid();
	}

}

// EOF
