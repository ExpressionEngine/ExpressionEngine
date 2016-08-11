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
		foreach ($this->steps as $step)
		{
			$this->runStep($step);
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
			$this->$step();
		}
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
