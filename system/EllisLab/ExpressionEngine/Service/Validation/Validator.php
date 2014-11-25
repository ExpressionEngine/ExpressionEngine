<?php
namespace EllisLab\ExpressionEngine\Service\Validation;

use InvalidArgumentException;

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
 * ExpressionEngine Validator
 *
 * @example
 *  $rules = array(
 *  	'name' => 'required|min_length[4]'
 *  );
 *
 * 	$validator = new Validator($rules);
 *  $result = $validator->validate($_POST);
 *
 *  // Or shorter using chaing. Given here with the DI notation:
 *
 *  $result = ('Validation', $rules)->validate($_POST);
 *
 * @package		ExpressionEngine
 * @subpackage	Validation
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Validator {

	protected $rules = array();
	protected $custom = array();
	protected $failed = array();

	/**
	 *
	 */
	public function __construct(array $rules = array())
	{
		$this->setRules($rules);
	}

	/**
	 *
	 */
	public function setRules($rules)
	{
		$this->rules = $rules;
	}

	/**
	 *
	 */
	public function setRule($key, $rule_string)
	{
		$this->rules[$key] = $rule_string;
	}

	/**
	 * Define a custom rule for this particular validator. These
	 * are *not* global definitions. They are one offs for this
	 * instance.
	 *
	 * @example
	 *  $validator->defineRule('instanceOf', function($value, $params)
	 *  {
	 * 		return is_a($value, $params[0]);
	 *  });
	 *
	 *  $validator->setRule('child', 'required|instanceOf[Name\Space\Class]');
	 *
	 * Callable type hint not available until PHP 5.4
	 *
	 * @param String   $name      The rule identifier as used in the rule string
	 * @param Callable $callback  Rule definition handler. Not limited to closures.
	 */
	public function defineRule($name,/* Callable*/ $callback)
	{
		if ( ! is_callable($callback))
		{
			throw new InvalidArgumentException('Rule callback must be callable');
		}

		$this->custom[$name] = new Rule\Callback($callback);
	}

	/**
	 *
	 */
	public function validate($values)
	{
		$result = new Result;

		foreach ($this->rules as $key => $rules)
		{
			$value = NULL;

			if (array_key_exists($key, $values))
			{
				$value = $values[$key];
			}

			$rules = $this->setupRules($rules);

			foreach ($rules as $rule)
			{
				$rule->setAllValues($values);

				if ($rule->validate($value) === FALSE)
				{
					if ($rule->skipsOnFailure())
					{
						break;
					}

					$result->addFailed($key, $rule);

					if ($rule->stopsOnFailure())
					{
						break;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Take the full piped rule string and create an array of
	 * validation rule objects.
	 */
	protected function setupRules($rules)
	{
		$rules = explode('|', $rules);
		$objects = array();

		foreach ($rules as $rule_string)
		{
			$objects[] = $this->setupRule($rule_string);
		}

		return $objects;
	}

	/**
	 * Parse a single rule string including any parameters passed
	 * and create the matching rule object.
	 *
	 * @param  string $rule_definition  The user specified rule string
	 * @return ValidationRule  The ValidationRule object
	 * @throws Exception  If the rule fails to load
	 */
	protected function setupRule($rule_definition)
	{
		list($name, $params) = $this->parseRuleString($rule_definition);

		if (isset($this->custom[$name]))
		{
			$object = $this->custom[$name];
		}
		else
		{
			$object = $this->newValidationRule($name);
		}


		if (isset($params))
		{
			if ( ! is_callable(array($object, 'setParameters')))
			{
				throw new \Exception("Validation rule `{$name}` does not accept parameters.");
			}

			$object->setParameters($params);
		}

		return $object;
	}

	/**
	 *
	 */
	protected function parseRuleString($string)
	{
		if (preg_match("/(.*?)\[(.*?)\]/", $string, $match))
		{
			$rule_name	= $match[1];
			$parameters	= $match[2];

			$parameters = explode(',', $parameters);
			$parameters = array_map('trim', $parameters);

			return array($rule_name, $parameters);
		}

		return array($string, NULL);
	}

	/**
	 *
	 */
	protected function newValidationRule($rule_name)
	{
		$rule_class = implode('', array_map('ucfirst', explode('_', $rule_name)));

		$class = __NAMESPACE__."\\Rule\\{$rule_class}";

		if (class_exists($class))
		{
			return new $class;
		}

		throw new \Exception("Unknown validation rule `{$rule_name}`.");
	}

}
