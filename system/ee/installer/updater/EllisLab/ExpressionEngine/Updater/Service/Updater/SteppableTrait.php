<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Service\Updater;

/**
 * This is a mock trait for unit tests. This file will be replaced with the
 * actual trait file upon build.
 */
trait SteppableTrait {

	/**
	 * Set the steps (method names) to run through
	 *
	 * @param	array	$steps	Method names
	 */
	public function setSteps(Array $steps)
	{
		$this->steps = $steps;
	}

	/**
	 * Runs an individual step
	 */
	public function runStep($step)
	{
		$this->currentStep = $step;
		$this->nextStep = NULL;

		list($step, $parameters) = $this->parseStepString($step);

		call_user_func_array([$this, $step], $parameters);
	}

}
// EOF
