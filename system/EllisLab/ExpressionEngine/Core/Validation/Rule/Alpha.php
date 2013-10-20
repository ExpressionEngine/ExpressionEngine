
<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class Alpha extends ValidationRule {
	
	public function validate($value)
	{
		return ( ! preg_match("/^([a-z])+$/i", $value)) ? FALSE : TRUE;
	}

}
