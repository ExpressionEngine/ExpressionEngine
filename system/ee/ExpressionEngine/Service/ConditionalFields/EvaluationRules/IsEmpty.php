<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\ConditionalFields\EvaluationRules;

/**
 * Is Empty Rule
 */
class IsEmpty extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    public function evaluate($fieldValue, $expectedValue)
    {
        return is_null($fieldValue) || $fieldValue !== '';
    }

    public function getConditionalFieldInputType()
    {
        return null;
    }
}
