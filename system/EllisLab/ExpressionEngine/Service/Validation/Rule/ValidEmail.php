<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule as ValidationRule;

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
		if (strpos($value, ',') === FALSE)
		{
			return EmailLibrary::getInstance()->isValidEmail(trim($value));
		}

		foreach(explode(',', $value) as $email)
		{
			if (trim($email) != '' && EmailLibrary::getInstance()->isValidEmail(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

}
