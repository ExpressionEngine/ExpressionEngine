<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Duration\EvaluationRules;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\EvaluationRuleInterface;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules;
use ExpressionEngine\Addons\Duration\Traits\DurationTrait;

/**
 * Less OR Equal Than duration Rule
 */
class DurationLessOrEqualThan extends EvaluationRules\LessOrEqualThan implements EvaluationRuleInterface
{
    use DurationTrait;

    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        $useSeconds = false;
        $multiplicator = 1;
        if (strpos($fieldValue, ':') || strpos($expectedValue, ':')) {
            $useSeconds = true;
            switch ($fieldSettings['units']) {
                case 'minutes':
                    $multiplicator = 60;
                    break;
                case 'hours':
                    $multiplicator = 60 * 60;
                    break;
                default:
                    break;
            }
        }
        if (strpos($fieldValue, ':')) {
            $fieldValue = $this->convertFromColonNotation($fieldValue, $fieldSettings['units']);
        } elseif ($useSeconds) {
            $fieldValue = $fieldValue * $multiplicator;
        }
        if (strpos($expectedValue, ':')) {
            $expectedValue = $this->convertFromColonNotation($expectedValue, $fieldSettings['units']);
        } elseif ($useSeconds) {
            $expectedValue = $expectedValue * $multiplicator;
        }

        return parent::evaluate($fieldValue, $expectedValue, $fieldSettings);
    }

    public function getLanguageKey()
    {
        return 'lessOrEqualThan';
    }
}
