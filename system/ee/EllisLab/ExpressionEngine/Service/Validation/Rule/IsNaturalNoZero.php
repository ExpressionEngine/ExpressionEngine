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
 * ExpressionEngine Non-zero Natural Number Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class IsNaturalNoZero extends ValidationRule {

	public function validate($key, $value)
	{
		if ( ! preg_match('/^[0-9]+$/', $value))
		{
			return FALSE;
		}

		return ($value > 0);
	}

	public function getLanguageKey()
	{
		return 'is_natural_no_zero';
	}

}

// EOF
