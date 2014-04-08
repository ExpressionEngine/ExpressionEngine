<?php
namespace EllisLab\ExpressionEngine\Library\Email;

/**
 *
 */
class EmailLibrary {

	/**
	 *
	 */
	public function validateEmail($email)
	{
		$email_rule = new Validation\Rule\ValidEmail();
		return $email_rule->validate($email);
	}

}
