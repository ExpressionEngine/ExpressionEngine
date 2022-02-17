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
 * Turned On Rule
 */
class TurnedOn extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    public function evaluate($fieldValue, $expectedValue)
    {
        return $fieldValue==='y' || $fieldValue==='1' || $fieldValue === true;
    }

    public function getConditionalFieldInputType()
    {
        return null;
    }
}
