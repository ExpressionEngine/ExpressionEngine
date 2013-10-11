
<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class IsNumeric extends ValidationRule {

	public function validate($value)
	{
		return ( ! is_numeric($value)) ? FALSE : TRUE;
	}

}
