<?php
namespace EllisLab\ExpressionEngine\Service\Validation;

/**
 *
 */
class ValidationService {

	protected static $namespaces = array(
		'EllisLab\\ExpressionEngine\\Service\\Validation\\Rule\\'
	);

	/**
	 *
	 */
	public static function registerRuleNamespace($namespace)
	{
		self::$namespaces[] = $namespace;
	}

	/**
	 *
	 */
	public static function getValidator()
	{
		return new Validator();
	}

	/**
	 *
	 */
	public static function parseRule($rule_definition)
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

		foreach(self::$namespaces as $namespace)
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
