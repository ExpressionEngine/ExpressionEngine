<div class="fluid-item" data-field-name="<?=$field_name?>" data-field-type="<?=$field->getType()?>">
	<div class="fluid-ctrls">
		<?php if ($reorderable): ?>
			<span class="reorder"></span>
		<?php endif ?>
		<h3>
			<span class="ico sub-arrow js-toggle-field"></span><?=$field->getItem('field_label')?>
			<?php if ($show_field_type): ?>
				<span class="faded">(<?=$field->getType()?>)</span>
			<?php endif ?>
		</h3>
		<a class="fluid-remove" href="" title=""></a>
		<?=$filters?>
	</div>
	<div class="fluid-field <?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid') echo 'fieldset-faux'; ?>">
		<div class="field-instruct">
			<em><?=$field->getItem('field_instructions')?></em>
		</div>
		<div class="field-control">
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
</div>
