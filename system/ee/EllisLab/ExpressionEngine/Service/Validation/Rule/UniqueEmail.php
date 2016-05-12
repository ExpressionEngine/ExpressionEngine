<?php

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine EmailUnique Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		
		if (get_bool_from_string($prevent) && strpos($value, '@gmail.com') !== FALSE)
		{
			$address = explode('@', $value);
			$query = ee()->db->query('SELECT REPLACE(REPLACE(LOWER(email), "@gmail.com", ""), ".", "") AS gmail
				FROM exp_members
				WHERE email LIKE "%gmail.com"
				HAVING gmail = "'.str_replace('.', '', $address[0]).'";');
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
