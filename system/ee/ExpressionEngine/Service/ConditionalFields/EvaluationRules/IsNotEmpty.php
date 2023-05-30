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
 * Is Not Empty Rule
 */
class IsNotEmpty extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        $isEmpty = is_null($fieldValue) 
            || $fieldValue === '' 
            || (is_array($fieldValue) && (
                empty($fieldValue) || count($fieldValue) == 1 && array_shift($fieldValue) === ''
            )
        );
        return !$isEmpty;
    }

    public function getConditionalFieldInputType()
    {
        return null;
    }
}
