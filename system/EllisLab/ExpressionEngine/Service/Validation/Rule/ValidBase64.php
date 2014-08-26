<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class ValidBase64 extends ValidationRule {

	public function validate($value)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $value);
	}

}
