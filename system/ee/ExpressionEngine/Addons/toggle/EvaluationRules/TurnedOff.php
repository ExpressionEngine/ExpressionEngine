<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\ToggleField\EvaluationRules;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\AbstractEvaluationRule;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\EvaluationRuleInterface;

/**
 * Turned Off Rule
 */
class TurnedOff extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    public function evaluate($fieldValue, $expectedValue)
    {
        return is_null($fieldValue) || $fieldValue==='n' || $fieldValue===0 || $fieldValue === false;
    }

    public function getConditionalFieldInputType()
    {
        return null;
    }
}
