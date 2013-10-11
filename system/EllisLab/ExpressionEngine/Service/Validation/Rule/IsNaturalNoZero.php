
<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

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
