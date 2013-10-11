
<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class Alpha extends ValidationRule {
	
	public function validate($value)
	{
		return ( ! preg_match("/^([a-z])+$/i", $value)) ? FALSE : TRUE;
	}

}
