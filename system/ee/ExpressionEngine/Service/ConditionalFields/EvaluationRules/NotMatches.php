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
 * NotMatches Rule
 */
class NotMatches extends AbstractEvaluationRule implements EvaluationRuleInterface
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
        if (is_array($fieldValue) && count($fieldValue) == 1) {
            $fieldValue = array_shift($fieldValue);
        }
        return $fieldValue !== $expectedValue;
    }

    public function getConditionalFieldInputType()
    {
        return 'select';
    }

    public function getLanguageKey()
    {
        return 'notEqual';
    }
}
