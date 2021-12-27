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
 * Abstract Evaluation Rule
 *
 */
abstract class AbstractEvaluationRule implements EvaluationRuleInterface
{
    /**
     * Evaluate the rule
     *
     * @param mixed $fieldValue
     * @param mixed $expectedValue
     * @return bool whether the condition is met
     */
    abstract public function evaluate($fieldValue, $expectedValue);

    /**
     * Get the language key used as label for the rule
     *
     * @return string
     */
    public function getLanguageKey()
    {
        return lcfirst(basename(str_replace('\\', '/', static::class)));
    }
    
    /**
     * The options for input that should be used to get a value for conditions involving this fieldtype
     * ex. [
     *  'type' => 'select',
     *  'options' => [...]
     * ]
     *
     * @return array
     */
    public function getConditionalFieldInputOptions()
    {
        return [
            'type' => 'text'
        ];
    }
}
