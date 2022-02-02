
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

// var_dump($data);

$fieldLabelArr = [];

foreach ($fieldsList as $fieldLabel) {
    $fieldLabelArr[$fieldLabel['field_id']] = $fieldLabel['field_label'];
}

// var_dump('errors', $errors);

var_dump($fieldLabelArr);

$rule = [
    'choices' => ee('View/Helpers')->normalizedChoices($fieldLabelArr),
    'value' => '',
    'too_many' => 10,
    'class' => 'condition-rule-field',
    'empty_text' => 'Select a Field',
    'field_name' => 'condition[new_set_0][new_row_0][condition_field_id]',
    'conditional_toggle' => 'rule',
    'is_required' => true
];

$allAny = [
    "all" => 'all',
    "any" => 'any',
];

$match = [
    'choices' => ee('View/Helpers')->normalizedChoices($allAny),
    'value' => 'all',
    'class' => 'condition-match-field',
    'field_name' => 'condition_set[new_set_0][match]',
];

ee()->javascript->set_global('conditionData', $data);

// var_dump($data);
?>
<div class="field-conditionset-wrapper">
    <?php $this->embed('ee:_shared/form/fields/condition-set', [
        'match' => $match,
        'rule' => $rule,
        'temlates' => true
    ]); ?>

    <?php if (count($data)):
        foreach ($data as $newData) {
            $conditionArr = array();
            $match['value'] = $newData['match'];

            foreach ($newData['conditions'] as $condition) {
                $setId = $condition["condition_set_id"];
                $match['field_name'] = 'condition_set[set_'.$condition["condition_set_id"].'][match]';
                array_push($conditionArr, $condition);
            }

            $this->embed('ee:_shared/form/fields/condition-set', [
                'match' => $match,
                'rule' => $rule,
                'savedSetId' => $setId,
                'conditionArr' => $conditionArr
            ]);
        }
    endif;?>
</div>
