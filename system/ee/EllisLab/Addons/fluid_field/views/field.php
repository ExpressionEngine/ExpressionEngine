<div class="fluid__item <?php if ($reorderable): ?>fluid__item--reorderable<?php endif ?>" data-field-name="<?=$field_name?>" data-field-type="<?=$field->getType()?>">
	<div class="fluid__item-content">
		<div class="fluid__item-fieldset reorder js-toggle-fluid-item">
			<div class="field-instruct">
				<label><?=$field->getItem('field_label')?> <?php if ($show_field_type): ?><span class="faded">(<?=$field->getType()?>)</span><?php endif ?></label>
				<em><?=$field->getItem('field_instructions')?></em>
			</div>
		</div>

		<div class="fluid__item-field no-drag <?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid') echo 'fieldset-faux'; ?>">
			<?php
				$field_name = $field->getName();
				$form = $field->getForm();

				if (strpos($form, 'name="' . $field_name . '"') === FALSE)
				{
					echo form_hidden($field_name, 1);
				}

				echo $form;
			?>
			<?=isset($errors) ? $errors->renderError($field_name) : ''?>
		</div>
	</div>
	<div class="fluid__item-tools fluid__item-tools--item-open">

		<?php if ( empty($is_bulk_edit)): ?>
		<a href data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle"><i class="fas fa-fw fa-cog"></i></a>
		<div class="dropdown">
			<a href class="dropdown__link js-toggle-fluid-item"><?=lang('collapse')?></a>
			<a href class="dropdown__link js-hide-all-fluid-items"><?=lang('collapse_all')?></a>
			<a href class="dropdown__link js-show-all-fluid-items"><?=lang('expand_all')?></a>
			<div class="dropdown__divider"></div>
			<a href class="dropdown__link danger-link js-fluid-remove"><i class="fas fa-fw fa-trash-alt"></i> <?=lang('delete')?></a>
		</div>
		<?php else: ?>
			<a href class="fluid__item-tool js-fluid-remove danger-link"><i class="fas fa-fw fa-trash-alt"></i></a>
		<?php endif; ?>

		<?php if ( empty($is_bulk_edit) AND isset($fields)): ?>
			<a href data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle"><i class="fas fa-fw fa-plus"></i></a>
			<div class="dropdown">
			<?php foreach ($fields as $field_item): ?>
				<a href="#" class="dropdown__link" data-field-name="<?=$field_item->getShortName()?>">
					<?=$field_item->getItem('field_label')?>
				</a>
			<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="fluid__item-tools fluid__item-tools--item-closed hidden">
		<a href class="fluid__item-tool js-toggle-fluid-item"><i class="fas fa-fw fa-angle-double-down"></i></a>
	</div>
</div>
