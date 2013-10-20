<?php
namespace EllisLab\ExpressionEngine\Core\Validation;

/**
 *
 */
class Validation {

	protected $namespaces = array(
		'EllisLab\\ExpressionEngine\\Service\\Validation\\Rule\\'
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
