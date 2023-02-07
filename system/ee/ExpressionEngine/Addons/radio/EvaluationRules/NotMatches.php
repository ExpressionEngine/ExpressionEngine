<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\RadioButtons\EvaluationRules;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\EvaluationRuleInterface;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules;

/**
 * Radio NotMatches Rule
 */
class NotMatches extends EvaluationRules\NotMatches implements EvaluationRuleInterface
{
    // for radio buttons, empty value is same as selecting first value
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        if (is_null($fieldValue) && isset($fieldSettings['options']) && !empty($fieldSettings['options']) && $expectedValue === array_key_first($fieldSettings['options'])) {
            return false;
        }
        return parent::evaluate($fieldValue, $expectedValue, $fieldSettings);
    }
}
