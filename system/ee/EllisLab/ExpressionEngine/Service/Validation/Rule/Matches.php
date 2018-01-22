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
 * Matches Validation Rule
 */
class Matches extends ValidationRule {

	protected $all_values = array();

	public function validate($key, $value)
	{
		foreach ($this->parameters as $field_name)
		{
			if ( ! array_key_exists($field_name, $this->all_values))
			{
				return isset($value); // both not set technically matches
			}

			if ($this->all_values[$field_name] != $value)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public function setAllValues(array $values)
	{
		$this->all_values = $values;
	}
}

// EOF
