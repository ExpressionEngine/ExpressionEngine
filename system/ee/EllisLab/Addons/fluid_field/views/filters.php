<a href="#" class="js-dropdown-toggle button button--secondary"><i class="icon--add"></i> <?=lang('add')?></a>
<div class="dropdown">
	<?php foreach ($fields as $field): ?>
		<a href="#" class="dropdown__link" data-field-name="<?=$field->getShortName()?>"><?=$field->getItem('field_label')?></a>
	<?php endforeach; ?>
</div>
