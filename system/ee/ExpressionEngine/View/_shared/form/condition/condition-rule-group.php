<?php

if (!empty($fieldConditionSets)) {
    $data = [];
    foreach ($fieldConditionSets->sortBy('order') as $fieldConditionSet) {
        $data[$fieldConditionSet->getId()] = [
            'match' => $fieldConditionSet->match,
            'conditions' => $fieldConditionSet->FieldConditions->sortBy('order')->toArray()
        ];
    }
}

$conditionFieldArr = [];

foreach ($fieldsList as $fieldLabel) {
    $conditionFieldArr[$fieldLabel['field_id']] = $fieldLabel['field_label'];
}

// var_dump('errors', $errors);

$conditionFieldDefault = [
    'choices' => ee('View/Helpers')->normalizedChoices($conditionFieldArr),
    'value' => '',
    'too_many' => 10,
    'class' => 'condition-rule-field',
    'empty_text' => 'Select a Field',
    'field_name' => 'condition[new_set_0][new_row_0][condition_field_id]',
    'conditional_toggle' => 'rule',
    'is_required' => true
];

$matchArrDefault = [
    "all" => 'all',
    "any" => 'any',
];

$matchFieldDefault = [
    'choices' => ee('View/Helpers')->normalizedChoices($matchArrDefault),
    'value' => 'all',
    'class' => 'condition-match-field',
    'field_name' => 'condition_set[new_set_0][match]',
];

ee()->javascript->set_global('conditionData', $data);

?>
<div class="field-conditionset-wrapper">
    <?php $this->embed('ee:_shared/form/condition/condition-set', [
        'matchVal' => $matchFieldDefault,
        'conditionFieldVal' => $conditionFieldDefault,
        'temlates' => true
    ]); ?>

    <?php if (count($data)):
        foreach ($data as $conditionSetId => $conditions) {
            $matchFieldDefault['value'] = $conditions['match'];

            $this->embed('ee:_shared/form/condition/condition-set', [
                'matchVal' => $matchFieldDefault,
                'conditionSetId' => $conditionSetId,
                'conditionFieldVal' => $conditionFieldDefault,
                'conditions' => $conditions
            ]);
        }
    endif;?>
</div>
