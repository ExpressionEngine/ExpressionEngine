<div class="fluid-item" data-field-name="<?=$field->field_name?>" data-field-type="<?=$field->field_type?>">
	<div class="fluid-ctrls">
		<span class="reorder"></span>
		<h3>
			<span class="ico sub-arrow js-toggle-field"></span><?=$field->field_label?>
			<span class="faded">(<?=$field->field_type?>)</span>
		</h3>
		<a class="fluid-remove" href="" title=""></a>
		<?=$filters?>
	</div>
	<div class="fluid-field <?php if ($field->field_type == 'grid') echo 'fieldset-faux'; ?>">
		<div class="field-instruct">
			<em><?=$field->field_instructions?></em>
		</div>
		<div class="field-control">
			<?php
				$field_name = $field->getField()->getName();
				$form = $field->getField()->getForm();

				if (strpos($form, 'name="' . $field_name . '"') === FALSE)
				{
					echo form_hidden($field_name, 1);
				}

				echo $form;
			?>
			<?=$errors->renderError($field_name)?>
		</div>
	</div>
</div>
