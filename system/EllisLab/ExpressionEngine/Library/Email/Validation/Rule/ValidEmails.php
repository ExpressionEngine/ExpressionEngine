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
class ValidEmails extends ValidationRule {

	public function validate($value)
	{
		$email_rule = new ValidEmail();
		if (strpos($value, ',') === FALSE)
		{
			return $email_rule->validate(trim($value));
		}

		foreach(explode(',', $value) as $email)
		{
			if (trim($email) != '' && $email_rule->validate(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

}
