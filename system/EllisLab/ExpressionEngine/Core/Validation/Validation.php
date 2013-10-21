<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 *
 */
class Validation {

	protected $namespaces = array(
		'EllisLab\\ExpressionEngine\\Core\\Validation\\Rule\\',
		'EllisLab\\ExpressionEngine\\Library\\Email\\Validation\\Rule',
		'EllisLab\\ExpressionEngine\\Library\\IpAddress\\Validation\\Rule'
	);

	/**
	 *
	 */
	public function registerRuleNamespace($namespace)
	{
		self::$namespaces[] = $namespace;
	}

	/**
	 *
	 */
	public static function getValidator()
	{
		return new Validator(self::$namespaces);
	}

}
