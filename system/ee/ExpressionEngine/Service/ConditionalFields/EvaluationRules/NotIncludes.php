<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\ConditionalFields\EvaluationRules;

/**
 * NotIncludes Rule
 */
class NotIncludes extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    /**
     * Evaluate the rule
     *
     * @param mixed $fieldValue
     * @param mixed $expectedValue
     * @return bool whether the condition is met
     */
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        if (!is_array($fieldValue)) {
            $fieldValue = array($fieldValue);
        }
        return !in_array($expectedValue, $fieldValue);
    }

    public function getConditionalFieldInputType()
    {
        return 'select';
    }
}
