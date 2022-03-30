<?php

$data = [];
if (!empty($fieldConditionSets)) {
    foreach ($fieldConditionSets->sortBy('order') as $fieldConditionSet) {
        $data[$fieldConditionSet->getId()] = [
            'match' => $fieldConditionSet->match,
            'conditions' => $fieldConditionSet->FieldConditions->sortBy('order')->toArray()
        ];
    }
}

$conditionFieldArr = [];

foreach ($fieldsList as $fieldLabel) {
    $conditionFieldArr[$fieldLabel['field_id']] = $fieldLabel['field_label'] . '<span class="short-name">{' . $fieldLabel['field_name'] . '}</span>';
}

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

// If this is loaded by an AJAX Request we need to add only a subset of 
// variables to the global EE javascript object.
if (AJAX_REQUEST) {
    echo ee()->javascript->get_global(['conditionData', 'fieldsInfo']);
}

?>
<div class="field-conditionset-wrapper">
    <?php $this->embed('ee:_shared/form/condition/condition-set', [
        'matchVal' => $matchFieldDefault,
        'conditionFieldVal' => $conditionFieldDefault,
        'temlates' => true
    ]); ?>

    <?php if (!empty($errors)):
        foreach ($errors->getFailed() as $fieldName => $item) {
            if (strpos($fieldName, 'condition') !== 0) {
                continue;
            }
            $fl_array = preg_match_all("/\[(.*?)\]/", $fieldName, $found);
            $setId = $found[1][0];
            $rowId = $found[1][1];

            if ($found[1][2] !== 'evaluation_rule') {
                if (strpos($setId, 'new_') !== false) {
                    $data[] = array(
                        'match' => 'all',
                        'conditions' => array(
                            array(
                                'condition_set_id' => $setId,
                                'condition_id' => $rowId,
                                'condition_field_id' => '',
                                'evaluation_rule' => '',
                                'value' => null,
                                'errors' => true
                            )
                        )
                    );
                } else {
                    $data[$setId]['conditions'][] = array(
                        'condition_set_id' => $setId,
                        'condition_id' => $rowId,
                        'condition_field_id' => '',
                        'evaluation_rule' => '',
                        'value' => null,
                        'errors' => true
                    );
                }
            }
        }
    endif;?>

    <?php if (count($data)):
        foreach ($data as $conditionSetId => $conditions) {
            $condSetId = $conditionSetId;
            if ($conditionSetId == 0) {
                $condSetId = $setId;
            }

            $matchFieldDefault['value'] = $conditions['match'];
            $matchFieldDefault['field_name'] = 'condition_set['. $condSetId .'][match]';

            if (!is_null($conditions['conditions'])) {
                $this->embed('ee:_shared/form/condition/condition-set', [
                    'matchVal' => $matchFieldDefault,
                    'conditionSetId' => $condSetId,
                    'conditionFieldVal' => $conditionFieldDefault,
                    'conditions' => $conditions
                ]);
            }
        }
    endif;?>
</div>
