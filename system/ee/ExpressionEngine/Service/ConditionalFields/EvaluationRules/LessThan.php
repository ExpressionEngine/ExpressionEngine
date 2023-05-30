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
 * Less Than Rule
 */
class LessThan extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        return $fieldValue < $expectedValue;
    }
}
