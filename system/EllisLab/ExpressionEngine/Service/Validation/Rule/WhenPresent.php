<?php
namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class WhenPresent extends ValidationRule {

	protected $all_values = array();
	protected $field_names = array();

	public function validate($value)
	{
		if (empty($this->field_names))
		{
			return isset($value);
		}

		foreach ($this->field_names as $name)
		{
			if ( ! array_key_exists($name, $this->all_values))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public function setParameters(array $field_names)
	{
		$this->field_names = $field_names;
	}

	public function setAllValues(array $values)
	{
		$this->all_values = $values;
	}

	public function skipsOnFailure()
	{
		return TRUE;
	}
}
