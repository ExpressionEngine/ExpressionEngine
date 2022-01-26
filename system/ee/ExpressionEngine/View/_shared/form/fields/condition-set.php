<?php 
	$rule = $rule;
	$match = $match;
	$template = isset($temlates) ? true : false;
	$setId = isset($temlates) ? 0 : 1;
?>

<div id="new_conditionset_block_<?=$setId?>" class="conditionset-item <?php if ($template):?>conditionset-temlates-row hidden <?php endif; ?>">
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
			<?=$this->embed('_shared/form/fields/condition-rule', [
				'setId' => $setId,
				'rule' => $rule,
				'hiddenTemplate' => true
			]);?>
		</div>

		<a href="#" class="button button--default button--small condition-btn" rel="add_row">Add a Condition</a>
	</div>

	<a href="#" class="add-set">Add another set...</a>
</div>