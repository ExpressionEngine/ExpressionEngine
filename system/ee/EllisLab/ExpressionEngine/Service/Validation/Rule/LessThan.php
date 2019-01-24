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
 * Less Than Validation Rule
 */
class LessThan extends ValidationRule {

	public function validate($key, $value)
	{
		list($compare) = $this->assertParameters('compare_to');

		$compare = $this->numericOrConstantParameter($compare);

		if ($compare === FALSE)
		{
			return FALSE;
		}

		return ($value < $compare);
	}

	public function getLanguageKey()
	{
		return 'less_than';
	}
}

// EOF
