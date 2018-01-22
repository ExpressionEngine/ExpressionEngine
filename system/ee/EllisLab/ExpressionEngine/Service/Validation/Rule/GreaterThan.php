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
 * Greater Than Validation Rule
 */
class GreaterThan extends ValidationRule {

	public function validate($key, $value)
	{
		list($compare) = $this->assertParameters('compare_to');

		$compare = $this->numericOrConstantParameter($compare);

		if ($compare === FALSE)
		{
			return FALSE;
		}

		return ($value > $compare);
	}

	public function getLanguageKey()
	{
		return 'greater_than';
	}
}

// EOF
