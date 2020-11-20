<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;
use EllisLab\ExpressionEngine\Service\Validation\Rule\Email;

/**
 * EmailUnique Validation Rule
 */
class UniqueEmail extends ValidationRule {

	/**
	 * Check to see if the email address is unique on the site
	 *
	 * @return boolean TRUE if it's unique, FALSE if it already exists
	 */
	public function validate($key, $value)
	{
		// Check for config, otherwise default
		$prevent = ee()->config->item('gmail_duplication_prevention') ?: 'y';

		//do we have a valid email address?
		$emailValid = new Email();
		$validEmail = $emailValid->validate($key, $value);

		if (!$validEmail) {
			// no valid email address kill it here
			return FALSE;
		}

		if (get_bool_from_string($prevent) && strpos($value, '@gmail.com') !== FALSE)
		{
			$address = explode('@', $value);
			$sql = 'SELECT REPLACE(REPLACE(LOWER(email), "@gmail.com", ""), ".", "") AS gmail
				FROM exp_members
				WHERE email LIKE "%gmail.com"
				HAVING gmail = "'.ee()->db->escape_str(str_replace('.', '', $address[0])).'";';
			$query = ee()->db->query($sql);

			$count = $query->num_rows();
		}
		else
		{
			$count = ee('Model')->get('Member')
				->filter('email', $value)
				->count();
		}

		return ($count <= 0);
	}

	public function getLanguageKey()
	{
		return 'unique_email';
	}

}

// EOF
