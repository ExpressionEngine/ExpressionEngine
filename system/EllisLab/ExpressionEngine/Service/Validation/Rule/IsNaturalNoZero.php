<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class IsNaturalNoZero extends ValidationRule {

	public function validate($value)
	{
		if ( ! preg_match( '/^[0-9]+$/', $value))
		{
			return FALSE;
		}

		if ($value == 0)
		{
			return FALSE;
		}

		return TRUE;
	}

}
