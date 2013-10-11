<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class AlphaNumeric extends ValidationRule {

	public function validate($value)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $value)) ? FALSE : TRUE;
	}

}
