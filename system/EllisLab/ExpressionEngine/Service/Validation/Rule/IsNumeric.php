<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class IsNumeric extends ValidationRule {

	public function validate($value)
	{
		return ( ! is_numeric($value)) ? FALSE : TRUE;
	}

}
