<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * URL Validation Rule
 */
class Url extends ValidationRule {

	public function validate($key, $value)
	{
		return (bool) filter_var($value, FILTER_VALIDATE_URL);
	}

	public function getLanguageKey()
	{
		return 'valid_url';
	}

}
