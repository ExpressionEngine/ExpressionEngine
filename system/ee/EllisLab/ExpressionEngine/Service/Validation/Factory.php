<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

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
 * ExpressionEngine Validator Factory
 *
 * @package		ExpressionEngine
 * @subpackage	Validation
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Factory {

	/**
	 * Make a new validator for a set of rules
	 */
	public function make($rules = array())
	{
		return new Validator($rules);
	}

	/**
	 * Check to see if a value passes a rule's validation
	 *
	 * @param  string $rule The rule to check
	 * @param  string $value The value to check
	 * @return boolean TRUE if the check passes
	 */
	public function check($rule, $value)
	{
		return $this->make(array('check' => $rule))
			->validate(array('check' => $value))
			->isValid();
	}

	/**
	 * Takes a model validation result object and checks for errors on the
	 * posted 'ee_fv_field' and returns an error message, or success message
	 * but only if the request was an AJAX request.
	 *
	 * @param EllisLab\ExpressionEngine\Service\Validation\Result $result A model validation result
	 * @return array|NULL NULL if the request was not via AJAX, otherwise an
	 *   an array with an error message or a success notification.
	 */
	public function ajax(ValidationResult $result)
	{
		if (ee()->input->is_ajax_request())
		{
			$field = ee()->input->post('ee_fv_field');

			// Get the parent field name
			$field = preg_replace('/\[.+?\]/', '', $field);

			if ($result->hasErrors($field))
			{
				return array('error' => $result->renderError($field));
			}
			else
			{
				return array('success');
			}
		}

		return NULL;
	}

}

// EOF
