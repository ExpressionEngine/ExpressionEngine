
<?php 

	$fieldLabelArr = [];

	foreach($fieldsList as $fieldLabel) {
		$fieldLabelArr[$fieldLabel['field_name']] = $fieldLabel['field_label'];
	}

	$rule = [
		'choices' => ee('View/Helpers')->normalizedChoices($fieldLabelArr),
		'value' => '',
		'too_many' => 50,
		'class' => 'condition-rule-field',
		'empty_text' => 'Select a Field',
		'field_name' => 'condition-rule-field',
	];

	$allAny = [
		"all" => 'All',
		"any" => 'Any',
	];

	$match = [
		'choices' => ee('View/Helpers')->normalizedChoices($allAny),
		'value' => 'all',
		'class' => 'condition-match-field',
		'field_name' => 'condition-match-field',
	]
?>
<div class="field-conditionset-wrapper">
	<div class="field-conditionset">
		<h4>
			Match
			<?=$this->embed('_shared/form/fields/dropdown', $match)?>
			conditions:
		</h4>

		<div class="rules">
			<div class="rule">
				<div class="condition-rule-field">
					<?=$this->embed('_shared/form/fields/dropdown', $rule)?>
				</div>
				<div class="condition-rule-operator">
					<select name="" id=""></select>
				</div>
				<div class="condition-rule-value">
					<input type="text">
				</div>
			</div>
		</div>

		<a href="#" class="button button--default button--small condition-btn">Add a Condition</a>
	</div>
	<a href="#" class="add-set">Add another set...</a>
</div>