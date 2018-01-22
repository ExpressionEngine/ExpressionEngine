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
 * Enum Validation Rule
 */
class Enum extends ValidationRule {

	public function validate($key, $value)
	{
		return in_array($value, $this->parameters);
	}

	/**
	 * Return the language data for the validation error.
	 */
	public function getLanguageData()
	{
		$list = implode(', ', $this->parameters);
		return array($this->getName(), $list);
	}

}

// EOF
