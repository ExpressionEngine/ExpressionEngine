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
 * Alphabetical and Dashes Validation Rule
 */
class AlphaDash extends ValidationRule {

	public function validate($key, $value)
	{
		return (bool) preg_match("/^([-a-z0-9_-])+$/i", $value);
	}

	public function getLanguageKey()
	{
		return 'alpha_dash';
	}
}

// EOF
