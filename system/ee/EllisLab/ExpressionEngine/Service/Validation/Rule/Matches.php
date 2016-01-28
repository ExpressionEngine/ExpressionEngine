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
 * ExpressionEngine Matches Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
