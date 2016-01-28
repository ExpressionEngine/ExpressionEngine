<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

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
 * ExpressionEngine Validation Rule Interface
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
 *
 * @package		ExpressionEngine
 * @subpackage	Validation
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
abstract class ValidationRule {

	const STOP = 'STOP';
	const SKIP = 'SKIP';

	/**
	 * @var array Rule parameters
	 */
	protected $parameters = array();

	/**
	 * @var string Stop/Skipped state
	 */
	protected $state = '';

	/**
	 * Validate a Value
	 *
	 * Validate a value against this rule. If it is valid, return TRUE
	 * otherwise, return FALSE.
	 *
	 * @param  mixed   $value  The value to validate.
	 * @return boolean Success?
	 */
	abstract public function validate($key, $value);

	/**
	 * Optional if you need access to other values
	 *
	 * Defaults to blank since we don't want to store
	 * all that information if we're not going to need it.
	 */
	public function setAllValues(array $values) { /* blank */ }

	/**
	 *
	 */
	public function setParameters(array $parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 *
	 */
	public function assertParameters()
	{
		$names = func_get_args();

		$count_needed = count($names);
		$count_given = count($this->parameters);

		if ($count_needed > $count_given)
		{
			$this->throwNeedsParameters(array_slice($names, $count_given));
		}

		return $this->parameters;
	}

	/**
	 * Hard failure. Will mark the rule as failed and stop processing rules
	 * for this field.
	 */
	public function stop()
	{
		$this->state = self::STOP;
	}

	/**
	 * Soft failure. Skips the rest of the validation process, but does not
	 * mark the rule as failed.
	 */
	public function skip()
	{
		$this->state = self::SKIP;
	}

	/**
	 * Report hard failure status.
	 *
	 * @return 	bool	Hard failure or not
	 */
	public function isStopped()
	{
		return $this->state == self::STOP;
	}

	/**
	 * Report soft failure status.
	 *
	 * @return 	bool	Soft failure or not
	 */
	public function isFailed()
	{
		return $this->state == self::SKIP;
	}

	/**
	 *
	 */
	public function getName()
	{
		return strtolower(basename(str_replace('\\', '/', get_class($this))));
	}

	/**
	 *
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 *
	 */
	public function getLanguageKey()
	{
		return $this->getName();
	}

	/**
	 * Return the language data for the validation error.
	 */
	public function getLanguageData()
	{
		return array($this->getLanguageKey(), $this->getParameters());
	}

	/**
	 *
	 */
	protected function throwNeedsParameters($missing = array())
	{
		$rule_id = "the {$this->getName()} validation rule";

		if (count($missing) == 1)
		{
			throw new \Exception("Missing {$missing[0]} parameter for {$rule_id}.");
		}

		$last = array_pop($missing);
		$init = implode(', ', $missing);

		throw new \Exception("Missing {$init} and {$last} parameters for {$rule_id}.");
	}
}