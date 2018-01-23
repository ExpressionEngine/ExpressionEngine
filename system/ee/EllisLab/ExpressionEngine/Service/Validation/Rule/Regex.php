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
 * Regular Expression Validation Rule
 */
class Regex extends ValidationRule {

	public function validate($key, $value)
	{
		list($regex) = $this->assertParameters('expression');

		return (bool) preg_match($regex, $value);
	}
}
