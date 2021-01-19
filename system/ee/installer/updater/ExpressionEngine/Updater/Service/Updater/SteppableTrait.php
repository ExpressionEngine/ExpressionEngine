<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Service\Updater;

/**
 * This is a mock trait for unit tests. This file will be replaced with the
 * actual trait file upon build.
 */
trait SteppableTrait
{

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
     * Runs an individual step
     */
    public function runStep($step)
    {
        $this->currentStep = $step;
        $this->nextStep = null;

        list($step, $parameters) = $this->parseStepString($step);

        call_user_func_array([$this, $step], $parameters);
    }
}
// EOF
