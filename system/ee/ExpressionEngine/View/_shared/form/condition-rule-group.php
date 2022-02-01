
<?php 

$fieldLabelArr = [];

foreach($fieldsList as $fieldLabel) {
	$fieldLabelArr[$fieldLabel['field_id']] = $fieldLabel['field_label'];
}

// var_dump('errors', $errors);

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

if (!empty($fieldConditionSets)) {
	$data = [];
	foreach($fieldConditionSets->sortBy('order') as $fieldConditionSet) {
		$data[$fieldConditionSet->getId()] = [
			'match' => $fieldConditionSet->match,
			'conditions' => $fieldConditionSet->FieldConditions->sortBy('order')->toArray()
		];
	}
}
var_dump($data);

ee()->javascript->set_global('conditionData', $data);
?>
<div class="field-conditionset-wrapper">
	<?php $this->embed('ee:_shared/form/fields/condition-set', [
		'match' => $match,
		'rule' => $rule,
		'temlates' => true
	]); ?>

</div>
