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

/**
 * Email Validation Rule
 */
class Email extends ValidationRule {

	public function validate($key, $value)
	{
		if ($value != filter_var($value, FILTER_SANITIZE_EMAIL) OR ! filter_var($value, FILTER_VALIDATE_EMAIL))
		{
			return FALSE;
		}

		return TRUE;
	}

	public function getLanguageKey()
	{
		return 'valid_email';
	}

}

// EOF
