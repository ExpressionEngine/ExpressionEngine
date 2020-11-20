<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Numeric Validation Rule
 */
class ValidateNumeric extends ValidationRule {

	public function validate($key, $value)
	{
		return (bool) preg_match('/^[+-]?([0-9]*\.[0-9]+|[0-9]+\.[0-9]*|[0-9]+)$/', $value);
	}

	public function getLanguageKey()
	{
		return 'is_numeric';
	}
}

// EOF
