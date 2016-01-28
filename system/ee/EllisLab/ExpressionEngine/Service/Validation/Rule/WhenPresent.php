<?php

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Presence Dependency Validation Rule
 *
 * 'nickname' => 'whenPresent|min_length[5]'
 * 'email' => 'whenPresent[newsletter]|email'
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
