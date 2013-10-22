<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 * Base Validation Rule
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
 */
abstract class ValidationRule {

	/**
	 * Construct a Validation Rule
	 *
	 * Validation rules must be able to take an array of parameters.
	 *
	 * @todo-DTB There's probably a better way to handle this. I'm not actually
	 * wild about defining a stub method that takes a parameter, and having it
	 * optionally take a parameter actually means this is meaningless.
	 */
	public function __construct(array $parameters=array())
	{

	}


	/**
	 * Validate a Value
	 *
	 * Validate a value against this rule.  If it is valid, return TRUE
	 * otherwise, return FALSE.  May use parameters passed in the constructor
	 * to perform the validation.
	 *
	 * @param	mixed	$value	The value to validate.
	 *
	 * @retun	boolean	On success, TRUE, on validation failure, FALSE.
	 */
	public abstract function validate($value);

}
