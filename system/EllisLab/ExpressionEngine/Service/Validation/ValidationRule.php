<?php
namespace EllisLab\ExpressionEngine\Service\Validation;

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
 * ExpressionEngine Validation Rule Interface
 *
 * Represents a Validation Rule that can be applied to a value during any
 * Validation stage.  This can be either form validation or validation of data
 * before it is committed to the database.  Will be loaded from a validation
 * string of the rule's name (first character lower case).
 *
 * For example, a rule to ensure that a required value is present might be
 * named "required".  It could be set in a validation string with other rules
 * such as "required|password".  The class definition would then look like
 * this::
 *
 * 	class Required extends ValidationRule {}
 *
 * @package		ExpressionEngine
 * @subpackage	Validation
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class ValidationRule {

	/**
	 * Validate a Value
	 *
	 * Validate a value against this rule. If it is valid, return TRUE
	 * otherwise, return FALSE.
	 *
	 * @param  mixed   $value  The value to validate.
	 * @return boolean Success?
	 */
	abstract public function validate($value);

	/**
	 * @return BOOL  Stop validating if this rule fails
	 */
	public function stopsOnFailure()
	{
		return FALSE;
	}

	/**
	 * @return BOOL  Rule is a presence indicator
	 */
	public function skipsOnFailure()
	{
		return FALSE;
	}

	/**
	 * Optional if you wish to support parameters
	 *
	 * Not actually defined here so that we can error when
	 * parameters are passed to a rule that does not take them.
	 */
	/*
	public function setParameters(array $parameters)
	*/

	/**
	 * Optional if you need access to other values
	 */
	public function setAllValues(array $values)
	{
		// nada, no need to store this information if it's not needed
	}

}