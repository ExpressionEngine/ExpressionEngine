<?php
// Show "Required Fields" in header if there are any required fields
if ( ! isset($required) || ! is_bool($required))
{
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
	}
} ?>

<h1><?=(isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title?><?php if ($required): ?> <span class="req-title"><?=lang('required_fields')?></span><?php endif ?></h1>
<?php if (isset($tabs)):?>
	<div class="tab-wrap">
		<ul class="tabs">
			<?php
				foreach (array_keys($tabs) as $i => $name):
					$class = '';
					if ($i == 0)
					{
						$class = 'act';
					}

					if (strpos($tabs[$name], 'class="ee-form-error-message"') !== FALSE)
					{
						$class .= ' invalid';
					}
				?>
				<li><a<?php if ($class) echo ' class="' . $class . '"'?> href="" rel="t-<?=$i?>"><?=lang($name)?></a></li>
			<?php endforeach; ?>
		</ul>
<?php endif; ?>
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
if ( ! isset($alerts_name))
{
	$alerts_name = 'shared-form';
}
?>
<?=form_open($base_url, $attributes, (isset($form_hidden)) ? $form_hidden : array())?>
	<?=ee('CP/Alert')->get($alerts_name)?>
	<?php
	if (isset($extra_alerts))
	{
		foreach ($extra_alerts as $alert)
		{
			echo ee('CP/Alert')->get($alert);
		}
	}
	if (isset($tabs)):
		foreach (array_values($tabs) as $i => $html):
	?>
		<div class="tab t-<?=$i?><?php if ($i == 0) echo ' tab-open'?>"><?=$html?></div>
	<?php
		endforeach;
	endif;

	$secure_form_ctrls = array();

	if (isset($sections['secure_form_ctrls']))
	{
		$secure_form_ctrls = $sections['secure_form_ctrls'];
		unset($sections['secure_form_ctrls']);
	}
	foreach ($sections as $name => $settings)
	{
		$this->embed('_shared/form/section', array('name' => $name, 'settings' => $settings));
	}

	// Set invalid class on secure form controls down below if it contains an invalid field
	$fieldset_classes = '';
	if (isset($errors) OR validation_errors())
	{
		foreach ($secure_form_ctrls as $setting)
		{
			if (validation_errors())
			{
				$fieldset_classes = form_error_class(array_keys($setting['fields']));

				if ( ! empty($fieldset_classes))
				{
					break;
				}
			}
			else
			{
				foreach (array_keys($setting['fields']) as $field)
				{
					if ($errors->hasErrors($field))
					{
						$fieldset_classes = 'invalid';
						break;
					}
				}
			}
		}
	}
	?>

	<fieldset class="form-ctrls <?=$fieldset_classes?>">
		<?php foreach ($secure_form_ctrls as $setting): ?>
			<div class="password-req required">
				<div class="setting-txt col w-8">
					<h3><?=lang($setting['title'])?></h3>
					<em><?=lang($setting['desc'])?></em>
				</div>
				<div class="setting-field col w-8 last">
					<?php foreach ($setting['fields'] as $field_name => $field)
					{
						$vars = array(
							'field_name' => $field_name,
							'field' => $field,
							'setting' => $setting,
							'grid' => FALSE
						);

						$this->embed('ee:_shared/form/field', $vars);
					}
				?>
				</div>
			</div>
		<?php endforeach ?>
		<?php if (isset($buttons)): ?>
			<?php foreach ($buttons as $button): ?>
				<?php
					$class = 'btn';

					$disabled = '';
					$button_text = lang($button['text']);

					if ((ee()->has('form_validation') && ee()->form_validation->errors_exist())
						OR (isset($errors) && $errors->isNotValid()))
					{
						$class = 'btn disable';
						$disabled = 'disabled="disabled"';
						$button_text = lang('btn_fix_errors');
					}

					if (isset($button['class']))
					{
						$class .= ' ' . $button['class'];
					}
				?>
				<button class="<?=$class?>" <?=$disabled?> name="<?=$button['name']?>" type="<?=$button['type']?>" value="<?=$button['value']?>" data-submit-text="<?=lang($button['text'])?>" data-work-text="<?=lang($button['working'])?>"><?=$button_text?></button>
			<?php endforeach; ?>
		<?php else: ?>
		<?=cp_form_submit($save_btn_text, $save_btn_text_working, NULL, (isset($errors) && $errors->isNotValid()))?>
		<?php endif; ?>
	</fieldset>
</form>
<?php if (isset($tabs)):?>
</div>
<?php endif; ?>
