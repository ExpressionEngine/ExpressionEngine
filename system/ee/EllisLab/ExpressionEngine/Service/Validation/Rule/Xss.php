<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * XSS Validation Rule
 */
class Xss extends ValidationRule {

	public function validate($key, $value)
	{
		return ($value == ee('Security/XSS')->clean($value)) ? TRUE : $this->stop();
	}

	public function getLanguageKey()
	{
		return sprintf(lang('invalid_xss_check'), ee('CP/URL')->make('homepage'));
	}

}
