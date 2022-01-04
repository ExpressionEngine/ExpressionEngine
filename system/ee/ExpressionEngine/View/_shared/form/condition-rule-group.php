
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
		'conditional_toggle' => 'rule'
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
	];

?>
<div class="field-conditionset-wrapper">
	<div id="new_conditionset_block_0" class="conditionset-item conditionset-temlates-row hidden">
		<a href="#" class="remove-set">
			<i class="fas fa-times alert__close-icon"></i>
		</a>
		<div class="field-conditionset">
			<h4>
				Match
				<?=$this->embed('_shared/form/fields/dropdown', $match)?>
				conditions:
			</h4>

			<div class="rules">
				<div class="rule rule-blank-row hidden" >
					<div class="condition-rule-field-wrap" data-new-rule-row-id="new_rule_row_0">
						<?=$this->embed('_shared/form/fields/dropdown', $rule)?>
					</div>

					<div class="condition-rule-operator-wrap" data-new-rule-row-id="new_rule_row_0">
						<select name="" id="" class="empty-select"></select>

						<div data-input-value="condition-rule-operator" class="condition-rule-operator" style="display: none;">
							<div class="fields-select-drop">
								<div class="select">
									<div class="select__button">
										<label class="select__button-label"></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="condition-rule-value-wrap" data-new-rule-row-id="new_rule_row_0">
						<input type="text">
					</div>

					<div class="delete_rule">
						<button type="button" rel="remove_row" class="button button--small button--default">
							<span class="danger-link" title="<?=lang('remove_row')?>"><i class="fas fa-trash-alt"><span class="hidden"><?=lang('remove_row')?></span></i></span>
						</button>
					</div>
				</div>

				<div class="rule">
					<div class="condition-rule-field-wrap">
						<?=$this->embed('_shared/form/fields/dropdown', $rule)?>
					</div>

					<div class="condition-rule-operator-wrap">
						<select name="" id="" class="empty-select"></select>

						<div data-input-value="condition-rule-operator" class="condition-rule-operator" style="display: none;">
							<div class="fields-select-drop">
								<div class="select">
									<div class="select__button">
										<label class="select__button-label"></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="condition-rule-value-wrap">
						<input type="text">
					</div>
				</div>
			</div>

			<a href="#" class="button button--default button--small condition-btn" rel="add_row">Add a Condition</a>
		</div>

		<a href="#" class="add-set">Add another set...</a>
	</div>

	<div class="conditionset-item">
		<a href="#" class="remove-set">
			<i class="fas fa-times alert__close-icon"></i>
		</a>

		<div class="field-conditionset">
			<h4>
				Match
				<?=$this->embed('_shared/form/fields/dropdown', $match)?>
				conditions:
			</h4>

			<div class="rules">
				<div class="rule rule-blank-row hidden" >
					<div class="condition-rule-field-wrap" data-new-rule-row-id="new_rule_row_0">
						<?=$this->embed('_shared/form/fields/dropdown', $rule)?>
					</div>

					<div class="condition-rule-operator-wrap" data-new-rule-row-id="new_rule_row_0">
						<select name="" id="" class="empty-select"></select>

						<div data-input-value="condition-rule-operator" class="condition-rule-operator" style="display: none;">
							<div class="fields-select-drop">
								<div class="select">
									<div class="select__button">
										<label class="select__button-label"></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="condition-rule-value-wrap" data-new-rule-row-id="new_rule_row_0">
						<input type="text">
					</div>

					<div class="delete_rule">
						<button type="button" rel="remove_row" class="button button--small button--default">
							<span class="danger-link" title="<?=lang('remove_row')?>"><i class="fas fa-trash-alt"><span class="hidden"><?=lang('remove_row')?></span></i></span>
						</button>
					</div>
				</div>

				<div class="rule">
					<div class="condition-rule-field-wrap">
						<?=$this->embed('_shared/form/fields/dropdown', $rule)?>
					</div>

					<div class="condition-rule-operator-wrap">
						<select name="" id="" class="empty-select"></select>

						<div data-input-value="condition-rule-operator" class="condition-rule-operator" style="display: none;">
							<div class="fields-select-drop">
								<div class="select">
									<div class="select__button">
										<label class="select__button-label"></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="condition-rule-value-wrap">
						<input type="text">
					</div>
				</div>
			</div>

			<a href="#" class="button button--default button--small condition-btn" rel="add_row">Add a Condition</a>
		</div>

		<a href="#" class="add-set">Add another set...</a>
	</div>

</div>