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
 * Minimum Length Validation Rule
 */
class MinLength extends ValidationRule {

	public function validate($key, $value)
	{

		ee()->load->helper('multibyte');

		list($length) = $this->assertParameters('length');

		$length = $this->numericOrConstantParameter($length);

		if ($length === FALSE)
		{
			return FALSE;
		}

		return (ee_mb_strlen($value) < $length) ? FALSE : TRUE;

	}

	public function getLanguageKey()
	{
		return 'min_length';
	}
}

// EOF
