<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Duration\EvaluationRules;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules\EvaluationRuleInterface;
use ExpressionEngine\Service\ConditionalFields\EvaluationRules;
use ExpressionEngine\Addons\Duration\Traits\DurationTrait;

/**
 * Greater OR Equal Than duration Rule
 */
class DurationGreaterOrEqualThan extends EvaluationRules\GreaterOrEqualThan implements EvaluationRuleInterface
{
    use DurationTrait;

    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        $fieldValue = $this->convertDurationToSeconds($fieldValue, $fieldSettings['units']);
        $expectedValue = $this->convertDurationToSeconds($expectedValue, $fieldSettings['units']);

        return parent::evaluate($fieldValue, $expectedValue, $fieldSettings);
    }

    public function getLanguageKey()
    {
        return 'greaterOrEqualThan';
    }
}
