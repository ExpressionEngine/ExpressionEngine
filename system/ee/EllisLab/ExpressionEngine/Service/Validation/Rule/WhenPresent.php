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
 * Presence Dependency Validation Rule
 *
 * 'nickname' => 'whenPresent|min_length[5]'
 * 'email' => 'whenPresent[newsletter]|email'
 */
class WhenPresent extends ValidationRule {

	protected $all_values = array();

	public function validate($key, $value)
	{
		if (empty($this->parameters))
		{
			return isset($value) ? TRUE : $this->skip();
		}

		foreach ($this->parameters as $field_name)
		{
			if ( ! array_key_exists($field_name, $this->all_values))
			{
				return $this->skip();
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
