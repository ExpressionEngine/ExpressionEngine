
<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class ValidBase64 extends ValidationRule {

	public function validate($value)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $value);
	}

}
