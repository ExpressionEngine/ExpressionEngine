<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 * Required
 * 
 * This value must be set and must not be an empty string.
 *
 * @param	mixed	$value	The value to validate.
 * 
 * @return	boolean	TRUE on pass, FALSE otherwise.
 */
class Required extends ValidationRule {

	public function validate($value)
	{
		if ( ! is_array($value))
		{
			return (trim($value) == '') ? FALSE : TRUE;
		}
		else
		{
			return ( ! empty($value));
		}
	}
}
