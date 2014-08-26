<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 * Validator
 * 
 * Performs validation on one or more passed value/rule string sets. Parses the
 * passed rule strings, loading the rules as it finds them.  It then tests the
 * value against each rule and stores any failed rules.  Must be initialized
 * with available rule namespaces in order to successfully find the needed
 * rules.
 *
 * @example
 * 	$validator = new Validator($namespaces);
 * 	if ( ! $validator->validate($value))
 * 	{
 * 		$errors = new Errors();
 * 		foreach($validator->getFailedRules() as $failed_rule)
 * 		{
 * 			$errors->addError(new ValidationError($failed_rule));
 * 		}
 *	}
 *
 */
class Validator {
	protected $failed_rules = array();
	protected $namespaces = array();

	/**
	 * Construct the Validator and Inject Namespaces
	 *
	 * Takes and stores the injected Validation Rule namespaces.
	 *
	 * @param	string[]	$namespaces	The fully qualified namespaces in which
	 * 		ValidationRules may reside.  In the order in which they were
	 * 		registered, EllisLab namespaces first.
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
     *					"min_length[6]|password"
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
				$this->failed_rules[] = $rule;	
			}
		}
		if ( ! empty($this->failed_rules))
		{
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a Rule Definition and Load the Rule Object
	 *
	 * Parses a rule string, including any parameters, and loads the necessary
	 * rule object.  Checks each injected namespace for the rule, in the order
	 * in which they were registered (EL namespaces first).  If a matching Rule
	 * is not found, throws an exception.  Once the rule is found, it
	 * instantiates it and passes the parsed parameters in the constructor.
	 *
	 * @param	string	$rule_definition	The definition of the rule to be
	 * 		loaded. Only a single rule allowed, not a full multiple rule 
	 * 		string.
	 *
	 * @return	ValidationRule	The instantiated ValidationRule object,
	 * 		initialized with the rules parameters.
	 *
	 * @throws	InvalidArgumentException	If the rule fails to load for any
	 * 		reason, an InvalidArgumentException is thrown.
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
