<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

use InvalidArgumentException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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
	protected function _validate($values, $partial = FALSE)
	{
		$result = new Result;

		if (is_object($values))
		{
			$values = $this->prepForObject($values);
		}

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
				if ($partial && $rule instanceOf Rule\Required && $value === NULL)
				{
					continue;
				}

				$rule->setAllValues($values);

				$rule_return = $rule->validate($key, $value);

				// Skip the rest of the rules?
				// e.g. Presence failed
				if ($rule->isFailed())
				{
					break;
				}

				// Hard stopping rule? Record the error and move on.
				// e.g. Required failed
				if ($rule->isStopped())
				{
					$result->addFailed($key, $rule);
					break;
				}

				// Passed? Move on to the next rule
				if ($rule_return === TRUE)
				{
					continue;
				}

				// At this point:
				//
				// 1) The rule failed (`$rule_return` === FALSE)
				// 2) This field is *not* required (no self::STOP)
				//
				// This means we have an incorrect optional value. Accordingly,
				// empty values are ok (because optional) anything else is not.
				if (is_string($value) && trim($value) !== '' ||
					(is_array($value) && ! empty($value)) ||
					is_numeric($value) ||
					is_object($value))
				{
					$result->addFailed($key, $rule);
				}
			}
		}

		return $result;
	}

	/**
	 * If we're passed an object we will try to interpret it as an array
	 * unless we're told that it knows how to do its own validation.
	 */
	protected function prepForObject($object)
	{
		if ( ! ($object instanceOf ValidationAware))
		{
			return (array) $object;
		}

		$values = $object->getValidationData();

		$this->setRules($object->getValidationRules());

		$callbacks = preg_grep('/^validate/', get_class_methods($object));

		foreach ($callbacks as $name)
		{
			$this->defineRule($name, function($key, $value, $params, $rule) use ($object, $name)
			{
				return $object->$name($key, $value, $params, $rule);
			});
		}

		return $values;
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
			$object = clone $this->custom[$name];
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

// EOF
