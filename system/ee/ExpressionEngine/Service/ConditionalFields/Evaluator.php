<?php

namespace ExpressionEngine\Service\ConditionalFields;

use ExpressionEngine\Field;

class Evaluator {

    public static function evaluate($expression)
    {
        if($expression instanceof Models\Condition) {
            return (new static)->evaluateCondition($expression);
        }elseif($expression instanceof Models\ConditionSet) {
            return (new static)->evaluateConditionSet($expression);
        }elseif($expression instanceof Contracts\Conditionable) {
            return (new static)->evaluateConditionableEntity($expression);
        }
    }

    public function evaluateCondition(Models\Condition $condition)
    {
        $operatorFunction = $condition->field->getFieldType()->getConditionalFieldOperator($condition->operator);

        return $operatorFunction($condition->field->getValue(), $condition->value);
    }

    public function evaluateConditionSet(Models\ConditionSet $set)
    {
        if(empty($set->conditions)) {
            return false;
        }

        foreach($set->conditions as $condition) {
            $result = $this->evaluateCondition($condition);

            // If the condition is true and the set should match any condition
            // we can stop evaluating and return true
            if($result && $set->match == 'any') {
                return true;
            }

            // If the condition is false and the set should match all conditions
            // we can stop evaluating and return false
            if(!$result && $set->match == 'all') {
                return false;
            }
        }

        // If we have gone through every condition without short-circuiting we
        // can assume that if the set was supposed to match 'any' conditions that
        // it did not and return false.  Likewise if the set was supposed to match
        // 'all' conditions and did not return early with false then it is true
        return ($set->match == 'any') ? false : true;
    }

    public function evaluateConditionableEntity(Contracts\Conditionable $entity)
    {
        if(empty($entity->getConditionSets())) {
            return false;
        }

        // Iterate through all of the entity's condition sets.
        // If any are false we will exit with a false value.
        foreach($entity->getConditionSets() as $set) {
            if(!$this->evaluateConditionSet($set)) {
                return false;
            }
        }

        return true;
    }


}