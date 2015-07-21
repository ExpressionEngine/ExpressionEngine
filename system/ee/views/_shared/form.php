<?php
// Show "Required Fields" in header if there are any required fields
$required = FALSE;
foreach ($sections as $name => $settings)
{
	foreach ($settings as $setting)
	{
		if ( ! is_array($setting))
		{
			continue;
		}

		foreach ($setting['fields'] as $field_name => $field)
		{
			if ($required = (isset($field['required']) && $field['required'] == TRUE))
			{
				break 3;
			}
		}
	}
} ?>

<h1><?=(isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title?><?php if ($required): ?> <span class="req-title"><?=lang('required_fields')?></span><?php endif ?></h1>
<?php
$form_class = 'settings';
if (isset($ajax_validate) && $ajax_validate == TRUE)
{
	$form_class .= ' ajax-validate';
}
$attributes = 'class="'.$form_class.'"';
if (isset($has_file_input) && $has_file_input == TRUE)
{
	$attributes .= ' enctype="multipart/form-data"';
}
?>
<?=form_open($base_url, $attributes, (isset($form_hidden)) ? $form_hidden : array())?>
	<?=ee('Alert')->get('shared-form')?>
	<?php
	if (isset($extra_alerts))
	{
		foreach ($extra_alerts as $alert)
		{
			echo ee('Alert')->get($alert);
		}
	}
	foreach ($sections as $name => $settings)
	{
		$this->embed('_shared/form/section', array('name' => $name, 'settings' => $settings));
	}
	?>
	<fieldset class="form-ctrls">
		<?php if (isset($buttons)): ?>
			<?php foreach ($buttons as $button): ?>
				<?php
					$class = 'btn';
					$disabled = '';
					$button_text = lang($button['text']);

					if (ee()->form_validation->errors_exist() OR (isset($errors) && $errors->isNotValid()))
					{
						$class = 'btn disable';
						$disabled = 'disabled="disabled"';
						$button_text = lang('btn_fix_errors');
					}
				?>
				<button class="<?=$class?>" <?=$disabled?> name="<?=$button['name']?>" type="<?=$button['type']?>" value="<?=$button['value']?>" data-submit-text="<?=lang($button['text'])?>" data-work-text="<?=lang($button['working'])?>"><?=$button_text?></button>
			<?php endforeach; ?>
		<?php else: ?>
		<?=cp_form_submit($save_btn_text, $save_btn_text_working, NULL, (isset($errors) && $errors->isNotValid()))?>
		<?php endif; ?>
	</fieldset>
</form>
