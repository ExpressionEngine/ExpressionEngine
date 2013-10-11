
<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class Integer extends ValidationRule {

	public function validate($value)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]+$/', $value);
	}

}
