<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Alphabetical and Numeric Validation Rule
 */
class AlphaNumeric extends ValidationRule {

	public function validate($key, $value)
	{
		return (bool) preg_match("/^([a-z0-9])+$/i", $value);
	}

	public function getLanguageKey()
	{
		return 'alpha_numeric';
	}
}

// EOF
