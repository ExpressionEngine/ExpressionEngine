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
 *  $result = ee('Validation')->make($rules)->validate($_POST);
 *
 * @package		ExpressionEngine
 * @subpackage	Validation
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Validator {

	const STOP = 'STOP';
	const SKIP = 'SKIP';

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
	 * Set rules for all items
	 */
	public function setRules($rules)
	{
		$this->rules = $rules;

		return $this;
	}

	/**
	 * Set rule for a single item
	 */
	public function setRule($key, $rule_string)
	{
		$this->rules[$key] = $rule_string;

		return $this;
	}

	/**
	 * Append a rule
	 */
	public function addRule($key, $rule_string)
	{
		if ( ! array_key_exists($key, $this->rules))
		{
			$this->rules[$key] = $rule_string;
		}
		else
		{
			$this->rules[$key] = $this->rules[$key].'|'.$rule_string;
		}

		return $this;
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
	 * Run the validation
	 *
	 * @param Array $values Data to validate
	 * @return Result object
	 */
	public function validate($values)
	{
		return $this->_validate($values);
	}

	/**
	 * Run partial validation
	 *
	 * Drops required rules for unset values, which is useful when you're
	 * updating an existing entity. If the value is passed as non-null (blank
	 * string, int 0, FALSE), then it will still trigger a required error,
	 * since updating such a value would invalidate the stored data.
	 *
	 * @param Array $values Data to validate
	 * @return Result object
	 */
	public function validatePartial($values)
	{
		return $this->_validate($values, TRUE);
	}

	/**
	 * Run the validation
	 *
	 * @param Array $values Data to validate
	 * @param Bool $partial Partial validation? (@see `validatePartial()`)
	 * @return Result object
	 */
	public function _validate($values, $partial = FALSE)
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
				if ($partial && $rule instanceOf Rule\Required && $value == NULL)
				{
					continue;
				}

				$rule->setAllValues($values);

				$rule_return = $rule->validate($value);

				// Passed? Move on to the next rule
				if ($rule_return === TRUE)
				{
					continue;
				}

				// Skip the rest of the rules?
				if ($rule_return === self::SKIP)
				{
					break;
				}

				// Hard stopping rule? Record the error and move on.
				if ($rule_return === self::STOP)
				{
					$result->addFailed($key, $rule);
					break;
				}

				// Add non-blank to failed
				if (trim($value) !== '')
				{
					$result->addFailed($key, $rule);
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
		$rules = explode('|', trim($rules, '|'));
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
			$object->setParameters($params);
		}

		return $object;
	}

	/**
	 * Split up the validation rule and its parameters
	 *
	 * @param String $string Validation rule
	 * @return Array [rule name, [...parameters]]
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
	 * Helper function to create a validation rule object
	 *
	 * @param String $rule_name Validation rule
	 * @return Object ValidationRule
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
