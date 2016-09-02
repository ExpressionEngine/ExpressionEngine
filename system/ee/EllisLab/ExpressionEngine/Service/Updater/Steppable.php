<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updater Steppable Trait
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
trait Steppable {

	protected $currentStep;

	/**
	 * Runs all steps in sequence
	 */
	public function run()
	{
		while (($next_step = $this->getNextStep()) !== FALSE)
		{
			$this->runStep($next_step);
		}
	}

	/**
	 * Runs an individual step
	 */
	public function runStep($step)
	{
		if (in_array($step, $this->steps))
		{
			$this->currentStep = $step;

			list($step, $parameters) = $this->parseStepString($step);

			$return = call_user_func_array([$this, $step], $parameters);

			// If we got a string back, we assume it's a method name with optional
			// parameters, insert it into the steps array to be called next
			if (is_string($return))
			{
				$index = array_search($this->currentStep, $this->steps);

				array_splice($this->steps, $index + 1, 0, $return);
			}
		}
	}

	/**
	 * Split up the step method name and its parameters, e.g. 'method[param1,param2]'
	 *
	 * @param	string	$string	Step method
	 * @return	array	[step method, [...parameters]]
	 */
	protected function parseStepString($string)
	{
		if (preg_match("/(.*?)\[(.*?)\]/", $string, $match))
		{
			$rule_name	= $match[1];
			$parameters	= $match[2];

			$parameters = explode(',', $parameters);
			$parameters = array_map('trim', $parameters);

			return [$rule_name, $parameters];
		}

		return [$string, []];
	}

	/**
	 * Gets the first step
	 *
	 * @return	string	Name of first step
	 */
	public function getFirstStep()
	{
		return $this->steps[0];
	}

	/**
	 * Gets the next step after the one that was most-recently run
	 *
	 * @return	string	Name of next step
	 */
	public function getNextStep()
	{
		if (empty($this->currentStep))
		{
			return $this->getFirstStep();
		}

		$index = array_search($this->currentStep, $this->steps);

		if ($index !== FALSE && isset($this->steps[$index+1]))
		{
			return $this->steps[$index+1];
		}

		return FALSE;
	}
}
// EOF
