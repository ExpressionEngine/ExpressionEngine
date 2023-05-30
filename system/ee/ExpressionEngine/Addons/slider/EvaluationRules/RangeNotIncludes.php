<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\SliderInput\EvaluationRules;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\AbstractEvaluationRule;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\EvaluationRuleInterface;

/**
 * Range Not Includes Rule
 */
class RangeNotIncludes extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        if (!is_array($fieldValue)) {
            return true;
        }
        sort($fieldValue);
        return !($expectedValue >= $fieldValue[0] && $expectedValue <= $fieldValue[1]);
    }

    public function getLanguageKey()
    {
        return 'notIncludes';
    }
}
