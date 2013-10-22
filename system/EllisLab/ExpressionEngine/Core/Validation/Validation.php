<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 * Validation
 *
 * Acts as a singleton on the Dependency Injection object (Dependencies) and
 * allows the registration of new Validation rule namespaces by third parties.
 * Also acts as a factory for Validator objects.
 */
class Validation {

	/**
	 * Validation rule namespaces that have been registered.  Initialized with
	 * EE's defaults such that EE's Validation Rules will always be loaded
	 * before any third party rules are loaded.
	 */
	protected $namespaces = array(
		'EllisLab\\ExpressionEngine\\Core\\Validation\\Rule\\',
		'EllisLab\\ExpressionEngine\\Library\\Email\\Validation\\Rule',
		'EllisLab\\ExpressionEngine\\Library\\IpAddress\\Validation\\Rule'
	);

	/**
	 * Register a Rule Namespace
	 * 	 
	 * Register a namespace in which Validation Rules reside.  This namespace
	 * will be examined when attempting to load a rule from a rule string.
	 *
	 * @param	string	$namespace	The fully qualified name of the namespace
	 * 		to be loaded.
	 *
	 * @return	void
	 */
	public function registerRuleNamespace($namespace)
	{
		self::$namespaces[] = $namespace;
	}

	/**
	 * Get A Validator Object
	 *
	 * Get a new validator object, initialized with the registered namespaces.
	 * This gets a default validator object that can validate a single value
	 * against a rule string at a time.  It will maintain an array of failed
	 * rules and return an Errors object containing any generated vaildation
	 * errors.
	 *
	 * @return	Validator	A new validator object, with namespaces injected.
	 */
	public static function getValidator()
	{
		return new Validator(self::$namespaces);
	}

}
