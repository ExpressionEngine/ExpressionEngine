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
 * Non-zero Natural Number Validation Rule
 */
class IsNaturalNoZero extends ValidationRule {

	public function validate($key, $value)
	{
		if ( ! preg_match('/^[0-9]+$/', $value))
		{
			return FALSE;
		}

		return ($value > 0);
	}

	public function getLanguageKey()
	{
		return 'is_natural_no_zero';
	}

}

// EOF
