<?php

namespace ExpressionEngine\Service\ConditionalFields;

use ExpressionEngine\Field;
use ExpressionEngine\Model\ConditionalFields;
use ExpressionEngine\Model\Channel\ChannelEntry;

class Evaluator
{
    protected $channelEntry;
    protected $channelFields;

    public function __construct($channelEntry)
    {
        $this->channelEntry = $channelEntry;
        $this->channelFields = $this->getFieldArray();
    }

    protected function getFieldArray()
    {
        // Loop through the channel entry fields and set a simple field_id => field_value array
        foreach ($this->channelEntry->getCustomFields() as $field) {
            $fields[$field->getId()] = $field;
        }

        return $fields;
    }

    public function evaluate($expression)
    {
        if ($expression instanceof ConditionalFields\FieldCondition) {
            return $this->evaluateCondition($expression);
        } elseif ($expression instanceof ConditionalFields\FieldConditionSet) {
            return $this->evaluateConditionSet($expression);
        } elseif (method_exists($expression, 'getConditionSets')) {
            return $this->evaluateConditionableEntity($expression);
        }
    }

    public function evaluateCondition(ConditionalFields\FieldCondition $condition)
    {
        // If this field isnt set on the channel entry, then we fail the conditions
        if (!isset($this->channelFields[$condition->condition_field_id])) {
            return false;
        }

        // Get the conditional field
        $fieldTypeName = $this->channelFields[$condition->condition_field_id]->getType();
        $fieldSettings = $this->channelFields[$condition->condition_field_id]->getSettings();
        $evaluationRule = ee('ConditionalFields')->make($condition->evaluation_rule, $fieldTypeName);

        // Radio field is special, we need to get options
        if ($fieldTypeName == 'radio') {
            $fieldSettings['options'] = $this->channelFields[$condition->condition_field_id]->getPossibleValuesForEvaluation();
        }

        // Now lets evaluate the condition_field value and the rule value
        return $evaluationRule->evaluate($this->channelFields[$condition->condition_field_id]->getData(), $condition->value, $fieldSettings);
    }

    public function evaluateConditionSet(ConditionalFields\FieldConditionSet $set)
    {
        if ($set->FieldConditions->count() === 0) {
            return false;
        }

        foreach ($set->FieldConditions as $condition) {
            $result = $this->evaluateCondition($condition);

            // If the condition is true and the set should match any condition
            // we can stop evaluating and return true
            if ($result && $set->match === 'any') {
                return true;
            }

            // If the condition is false and the set should match all conditions
            // we can stop evaluating and return false
            if (!$result && $set->match === 'all') {
                return false;
            }
        }

        // If we have gone through every condition without short-circuiting we
        // can assume that if the set was supposed to match 'any' conditions that
        // it did not and return false.  Likewise if the set was supposed to match
        // 'all' conditions and did not return early with false then it is true
        return ($set->match === 'any') ? false : true;
    }

    public function evaluateConditionableEntity($entity)
    {
        if (!method_exists($entity, 'getConditionSets')) {
            throw new \Exception(get_class($entity) . " needs to implement getConditionSets() to be evaluated as conditional.", 1);
        }

        if (empty($entity->getConditionSets())) {
            return false;
        }

        foreach ($entity->getConditionSets() as $set) {
            if (!$this->evaluateConditionSet($set)) {
                return false;
            }
        }

        return true;
    }
}
