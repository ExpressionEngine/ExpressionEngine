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
 * Required Validation Rule
 */
class Required extends ValidationRule {

	public function validate($key, $value)
	{
		if ( ! is_array($value))
		{
			$value = trim($value);
		}

		if ($value === '' OR is_null($value))
		{
			return $this->stop();
		}

		return TRUE;
	}
}
