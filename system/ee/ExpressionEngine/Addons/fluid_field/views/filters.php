<?php if (count($fields) < 20) : ?>

	<?php foreach ($fields as $field): ?>
		<a href="#" class="button button--auto button--default button--small" data-field-name="<?=$field->getShortName()?>">
			<img src="<?=$field->getIcon()?>" width="16" height="16" alt="<?=lang('add')?> <?=$field->getItem('field_label')?>" /><br />
			<?=lang('add')?> <?=$field->getItem('field_label')?>
		</a>
	<?php endforeach; ?>

<?php else: ?>

	<a href="javascript:void(0)" class="js-dropdown-toggle button button--auto button--default button--small"><i class="fa-2x icon--add"></i><br /> <?=lang('add_field')?></a>
	<div class="dropdown">
		<?php foreach ($fields as $field): ?>
			<a href="#" class="dropdown__link" data-field-name="<?=$field->getShortName()?>"><img src="<?=$field->getIcon()?>" width="12" height="12" /> <?=$field->getItem('field_label')?></a>
		<?php endforeach; ?>
	</div>

<?php endif; ?>