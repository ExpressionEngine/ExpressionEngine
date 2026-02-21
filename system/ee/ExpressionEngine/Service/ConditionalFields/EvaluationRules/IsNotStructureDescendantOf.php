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
 * Is Not Structure Descendant Of Rule
 */
class IsNotStructureDescendantOf extends AbstractEvaluationRule implements EvaluationRuleInterface
{
    /**
     * Evaluate the rule
     *
     * @param mixed $fieldValue
     * @param mixed $expectedValue
     * @return bool whether the condition is met
     */
    public function evaluate($fieldValue, $expectedValue, $fieldSettings)
    {
        $expectedValue = trim($expectedValue, "_");
        require_once PATH_ADDONS . 'structure/sql.structure.php';
        $sql = new \Sql_structure();
        do {
            $fieldValue = $sql->get_parent_id($fieldValue, 0);
            if ($fieldValue == $expectedValue) {
                return false;
            }
        } while ($fieldValue != 0);

        return true;
    }

    public function getConditionalFieldInputType()
    {
        return 'select';
    }
}
