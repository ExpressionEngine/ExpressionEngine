<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater;

/**
 * This is a handy queue-like system which allows you to iterate over
 * pre-defined steps to accomplish a goal. You can inject steps at any point
 * and start the iterator from any point.
 */
trait SteppableTrait
{
    /**
     * Stores list of methods to call on the implementing class
     */
    private $steps;

    /**
     * Stores the current step being run
     */
    private $currentStep;

    /**
     * Stores the next step if one is returned by the current step
     */
    private $nextStep;

    /**
     * Set the steps (method names) to run through
     *
     * @param	array	$steps	Method names
     */
    public function setSteps(array $steps)
    {
        $this->steps = $steps;
    }

    /**
     * Sets the next step to be called. After that step is called, the queue
     * will pick up where it left off.
     *
     * @param	string	$steps	Method name, with optional parameters, e.g.:
     *   'methodName[1,2]'
     */
    public function setNextStep($step)
    {
        list($method, $parameters) = $this->parseStepString($step);

        if (! method_exists($this, $method)) {
            throw new UpdaterException('Method does not exist on this Steppable class: ' . $method, 20);
        }

        $index = array_search($this->currentStep, $this->steps);

        if ($index === false or in_array($step, $this->steps)) {
            $this->nextStep = $step;

            return;
        }

        // Inject this step into our steps array
        if (! in_array($step, $this->steps)) {
            array_splice($this->steps, $index + 1, 0, $step);
        }
    }

    /**
     * Runs all steps in sequence
     */
    public function run()
    {
        while (($next_step = $this->getNextStep()) !== false) {
            $this->runStep($next_step);
        }
    }

    /**
     * Runs an individual step
     */
    public function runStep($step)
    {
        $this->currentStep = $step;
        $this->nextStep = null;

        list($step, $parameters) = $this->parseStepString($step);

        call_user_func_array([$this, $step], $parameters);
    }

    /**
     * Split up the step method name and its parameters, e.g. 'method[param1,param2]'
     *
     * @param	string	$string	Step method
     * @return	array	[step method, [...parameters]]
     */
    protected function parseStepString($string)
    {
        if (preg_match("/(.*?)\[(.*?)\]$/", $string, $match)) {
            $rule_name = $match[1];
            $parameters = $match[2];

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
        return isset($this->steps[0]) ? $this->steps[0] : false;
    }

    /**
     * Gets the next step after the one that was most-recently run
     *
     * @return	string	Name of next step
     */
    public function getNextStep()
    {
        if (empty($this->currentStep)) {
            return $this->getFirstStep();
        }

        if (! is_null($this->nextStep)) {
            return $this->nextStep;
        }

        $index = array_search($this->currentStep, $this->steps);

        if ($index !== false && isset($this->steps[$index + 1])) {
            return $this->steps[$index + 1];
        }

        return false;
    }
}
// EOF
