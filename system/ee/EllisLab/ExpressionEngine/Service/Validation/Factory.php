<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Validation;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
	 * Make a new validator for a set of rules
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
