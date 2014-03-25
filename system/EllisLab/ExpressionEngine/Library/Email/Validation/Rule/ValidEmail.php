<?php
namespace EllisLab\ExpressionEngine\Library\Email\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 * Valid Email
 *
 * @access	public
 * @param	string
 * @param	value
 * @return	bool
 */
class ValidEmail extends ValidationRule {

	public function validate($value)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) ? FALSE : TRUE;
	}

}
