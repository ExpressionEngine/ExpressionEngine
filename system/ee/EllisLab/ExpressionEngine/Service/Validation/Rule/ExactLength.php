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
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Exact Length Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ExactLength extends ValidationRule {

	public function validate($key, $value)
	{
		list($length) = $this->assertParameters('length');

		if (preg_match("/[^0-9]/", $length))
		{
			return FALSE;
		}

		if (function_exists('mb_strlen'))
		{
			return (mb_strlen($value) != $length) ? FALSE : TRUE;
		}

		return (strlen($value) != $length) ? FALSE : TRUE;
	}

	public function getLanguageKey()
	{
		return 'exact_length';
	}
}

// EOF
