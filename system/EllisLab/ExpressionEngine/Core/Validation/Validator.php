<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 * Validation Service 
 * 
 * A library which may be used to apply validation rules to a value.  May be
 * used to perform simple validation in multiple contexts.  Validates only
 * a single value at a time and produces an array of any rules that the value
 * failed to pass.
 *
 * @example
 *		$validator = new Validator();
 *		$messages = array();
 * 		if ( ! $validator->validate($value, 'max_length[10]|password') )
 *		{
 *			$failed_rules = $validator->getFailedRules();
 *			foreach($failed_rules as $failed_rule)
 *			{
 *				$messages[] = lang($failed_rule);
 *			}
 *		}
 *
 */
class Validator {
	protected $failed_rules = array();
	protected $namespaces = array();

	/**
	 *
	 */
	public function __construct($namespaces)
	{
		$this->namespaces = $namespaces;
	}


	// --------------------------------------------------------------------

	/**
	 * Get an array of Failed Validation Rules
	 *
	 * Returns an array of failed validation rules that can be used to generate
 	 * error messages.  
	 * 
 	 * @return	mixed[]	An array rules that failed to validate. 
	 *				Example: array('max_length', 'password')
	 */
	public function getFailedRules()
	{
		return $this->failed_rules;
	}

	// --------------------------------------------------------------------

	/**
	 * Apply a set of Validation Rules to a Value
	 *
	 * Apply a set of validation rules, given in piped string format, to a 
	 * passed value.  Will return true if the value validates based on
	 * the given rules.  Will return false, and set the errors array
	 * if it doesn't.
	 *
	 * @param	string	$rule_definitions	The rules to validate based 
	 *				on in piped string format.  For example: 
     *				min_length[6]|password.
	 * @param	mixed	$value	The value to validate.
	 * 
	 * @return	boolean	True on success, FALSE otherwise.  On a FALSE return
	 * 				any rules that failed will be set in the errors array and
	 * 				may be retrieved with getFailedRules().
	 */
	public function validate($rule_definitions, $value)
	{
		$rule_definitions = explode('|', $rule_definitions);
		foreach($rule_definitions as $rule_definition)
		{
			$rule = ValidationService::parseRule($rule_definition);	
			if ( ! $rule->validate($value))
			{
				$this->failed_rules[] = $rule_definition;	
			}
		}
		if ( ! empty($this->failed_rules))
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 *
	 */
	protected function parseRule($rule_definition)
	{
		if (preg_match("/(.*?)\[(.*?)\]/", $rule_definition, $match))
		{
			$rule_name	= $match[1];
			$parameters	= $match[2];

			if (strpos(',', $parameters) !== FALSE)
			{
				$parameters = explode(',', $parameters);
			}
			else
			{
				$parameters = array($parameters);
			}
		}
		else
		{
			$rule_name = $rule_definition;
			$parameters = array();
		}

		foreach($this->namespaces as $namespace)
		{
			$fully_qualified_class = $namespace . ucfirst($rule_name);
			if (class_exists($fully_qualified_class))
			{
				$rule = new $fully_qualified_class($parameters);
				return $rule;
			}
		}

		throw new InvalidArgumentException('Non-existent ValidationRule, "' . $rule_definition . '", requested in validation!');
	}
}
