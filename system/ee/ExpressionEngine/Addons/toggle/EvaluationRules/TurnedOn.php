<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        // If is null, set the value to the default value
        if (is_null($fieldValue)) {
            $fieldValue = $fieldSettings['field_default_value'];
        }

        return get_bool_from_string($fieldValue);
    }

    public function getConditionalFieldInputType()
    {
        return null;
    }
}
