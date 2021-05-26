<div class="fluid__item <?php if ($reorderable): ?>fluid__item--reorderable<?php endif ?>" data-field-name="<?=$field_name?>" data-field-type="<?=$field->getType()?>">
	<div class="fluid__item-content">
		<div class="fluid__item-fieldset ">

      <div class="fluid__item-tools fluid__item-tools--item-open">

    		<?php if (empty($is_bulk_edit)): ?>
    		<button type="button" data-dropdown-offset="0px, -30px" data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle"><i class="fas fa-fw fa-cog"></i></button>
    		<div class="dropdown">
    			<a href class="dropdown__link js-toggle-fluid-item"><?=lang('collapse')?></a>
    			<a href class="dropdown__link js-hide-all-fluid-items"><?=lang('collapse_all')?></a>
    			<a href class="dropdown__link js-show-all-fluid-items"><?=lang('expand_all')?></a>
    			<div class="dropdown__divider"></div>
    			<a href class="dropdown__link dropdown__link--danger js-fluid-remove"><i class="fas fa-fw fa-trash-alt"></i> <?=lang('delete')?></a>
    		</div>
    		<?php else: ?>
    			<button type="button" class="fluid__item-tool js-fluid-remove danger-link" title="<?=lang('remove')?>"><i class="fas fa-fw fa-trash-alt"></i></button>
    		<?php endif; ?>

    		<?php if (empty($is_bulk_edit) and isset($fields)): ?>
    			<button type="button" data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle" title="<?=lang('add_field')?>"><i class="fas fa-fw fa-plus"></i></button>
    			<div class="dropdown">
    			<?php foreach ($fields as $field_item): ?>
    				<a href="#" class="dropdown__link" data-field-name="<?=$field_item->getShortName()?>"><img src="<?=$field_item->getIcon()?>" width="12" height="12" /> 
    					<?=$field_item->getItem('field_label')?>
    				</a>
    			<?php endforeach; ?>
    			</div>
    		<?php endif; ?>
    	</div>

      <div class="field-instruct">
				<label>
					<i class="icon--reorder reorder"></i>
					<?=$field->getItem('field_label')?> <?php if ($show_field_type): ?><span class="faded">(<?=$field->getType()?>)</span><?php endif ?>
				</label>
				<em><?=$field->getItem('field_instructions')?></em>
			</div>
		</div>

		<div class="fluid__item-field no-drag <?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid') {
    echo 'fieldset-faux';
} ?>">
			<?php
                $field_name = $field->getName();
                $form = $field->getForm();

                if (strpos($form, 'name="' . $field_name . '"') === false) {
                    echo form_hidden($field_name, 1);
                }

                echo $form;
            ?>
			<?=isset($errors) ? $errors->renderError($field_name) : ''?>
		</div>
	</div>

	<div class="fluid__item-tools fluid__item-tools--item-closed hidden">
		<button type="button" class="fluid__item-tool js-toggle-fluid-item" title="<?=lang('expand')?>"><i class="fas fa-fw fa-angle-double-down"></i></button>
	</div>
</div>
