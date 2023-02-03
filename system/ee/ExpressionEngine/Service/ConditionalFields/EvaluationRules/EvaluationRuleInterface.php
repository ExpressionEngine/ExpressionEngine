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
 * Evaluation Rule Interface
 *
 * Sets the pattern for individual evaluation rules
 */
interface EvaluationRuleInterface
{
    /**
     * Evaluate the rule
     *
     * @param mixed $fieldValue
     * @param mixed $expectedValue
     * @param array $fieldSettings
     * @return bool whether the condition is met
     */
    public function evaluate($fieldValue, $expectedValue, $fieldSettings);

    /**
     * Get the language key used as label for the rule
     *
     * @return string
     */
    public function getLanguageKey();

    /**
     * The input type for the expected value (text, select, etc)
     * If returning NULL, the field is not displayed
     *
     * @return mixed
     */
    public function getConditionalFieldInputType();
}
