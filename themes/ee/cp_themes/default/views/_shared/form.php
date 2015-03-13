<?php
// Show "Required Fields" in header if there are any required fields
$required = FALSE;
foreach ($sections as $name => $settings)
{
	foreach ($settings as $setting)
	{
		foreach ($setting['fields'] as $field_name => $field)
		{
			if ($required = (isset($field['required']) && $field['required'] == TRUE))
			{
				break 3;
			}
		}
	}
} ?>

<h1><?=(isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title?><?php if ($required): ?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span><?php endif ?></h1>
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
	<?php if (isset($extra_alerts)): ?>
		<?php foreach ($extra_alerts as $alert) echo ee('Alert')->get($alert) ?>
	<?php endif; ?>
	<?php foreach ($sections as $name => $settings): ?>
		<?php if (is_string($name)): ?>
			<h2><?=lang($name)?></h2>
		<?php endif ?>
		<?php foreach ($settings as $setting): ?>
			<?php

			// If a string is passed, just display the string
			if (is_string($setting))
			{
				echo $setting;
				continue;
			}

			$last_class = ($setting == end($settings)) ? ' last' : '';
			$grid = (isset($setting['grid']) && $setting['grid'] == TRUE);

			// Grids have to be in a div for an overflow bug in Firefox
			$element = ($grid) ? 'div' : 'fieldset'; ?>
			<<?=$element?> class="col-group<?=$last_class?> <?=( ! $grid) ? form_error_class(array_keys($setting['fields'])) : '' ?> <?=($grid) ? 'grid-publish' : '' ?>">
				<div class="setting-txt col <?=($grid) ? form_error_class(array_keys($setting['fields'])) : '' ?> <?=(isset($setting['wide']) && $setting['wide'] == TRUE) ? 'w-16' : 'w-8'?>">
					<?php foreach ($setting['fields'] as $field_name => $field)
					{
						if ($required = (isset($field['required']) && $field['required'] == TRUE))
						{
							break;
						}
					}
					$security = (isset($setting['security']) && $setting['security'] == TRUE);
					$caution = (isset($setting['caution']) && $setting['caution'] == TRUE);
					?>
					<h3<?php if ($security):?> class="enhance"<?php endif ?><?php if ($caution):?> class="caution"<?php endif ?>>
						<?=lang($setting['title'])?>
						<?php if ($required): ?> <span class="required" title="required field">&#10033;</span><?php endif ?>
						<?php if ($security): ?> <span title="enhance security"></span><?php endif ?>
						<?php if ($caution): ?> <span title="enhance caution"></span><?php endif ?>
					</h3>
					<em><?=lang($setting['desc'])?></em>
				</div>
				<div class="setting-field col <?=(isset($setting['wide']) && $setting['wide'] == TRUE) ? 'w-16' : 'w-8'?> last">
					<?php foreach ($setting['fields'] as $field_name => $field):
						// Get the value of the field
						$value = set_value($field_name);
						if ($value == '')
						{
							$value = isset($field['value']) ? $field['value'] : ee()->config->item($field_name);
						}
						$required = '';
						if (isset($field['required']) && $field['required'] == TRUE)
						{
							$required = ' class="required"';
						}
						$has_note = isset($field['note']);

						$no_results = (in_array($field['type'], array('checkbox', 'radio', 'dropdown')) &&
							isset($field['no_results']) &&
							count($field['choices']) == 0);
						?>
						<?php if ($no_results): ?>
							<div class="no-results">
								<p><?=lang($field['no_results']['text'])?></p>
								<p><a class="btn action" href="<?=lang($field['no_results']['link_href'])?>">
									<?=lang($field['no_results']['link_text'])?>
								</a></p>
							</div>
						<?php continue; endif ?>
						<?php if ($has_note): ?>
							<div class="setting-note">
						<?php endif ?>
						<?php switch ($field['type']):
						case 'text': ?>
							<input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$required?>>
						<?php break;
						case 'file': ?>
							<input type="file" name="<?=$field_name?>"<?=$required?>>
						<?php break;
						case 'password': ?>
							<input type="password" name="<?=$field_name?>"<?=$required?>>
						<?php break;
						case 'hidden': ?>
							<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
						<?php break;

						case 'radio': ?>
							<?php foreach ($field['choices'] as $key => $label):
								$checked = ($key == $value); ?>
								<label class="choice mr block <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$required?>> <?=lang($label)?></label>
							<?php endforeach ?>
							<?php if ( ! empty($field['html'])): ?><?=$field['html']?><?php endif ?>
						<?php break;

						case 'inline_radio': ?>
							<?php foreach ($field['choices'] as $key => $label):
								$checked = ((is_bool($value) && get_bool_from_string($key) === $value) OR ( ! is_bool($value) && $key == $value)); ?>
								<label class="choice mr <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$required?>> <?=lang($label)?></label>
							<?php endforeach ?>
						<?php break;

						case 'yes_no': ?>
							<label class="choice mr<?php if (get_bool_from_string($value)):?> chosen<?php endif ?> yes"><input type="radio" name="<?=$field_name?>" value="y"<?php if (get_bool_from_string($value)):?> checked="checked"<?php endif ?><?=$required?>> yes</label>
							<label class="choice <?php if (get_bool_from_string($value) === FALSE):?> chosen<?php endif ?> no"><input type="radio" name="<?=$field_name?>" value="n"<?php if (get_bool_from_string($value) === FALSE):?> checked="checked"<?php endif ?><?=$required?>> no</label>
						<?php break;

						case 'dropdown': ?>
							<?=form_dropdown($field_name, $field['choices'], $value, $required)?>
						<?php break;

						case 'checkbox': ?>
							<div class="scroll-wrap">
								<?php foreach ($field['choices'] as $key => $label):
									if (is_array($value))
									{
										$selected = in_array($key, $value);
									}
									else
									{
										$selected = ($value == $key);
									}
								?>
									<label class="choice block<?php if ($selected):?> chosen<?php endif ?>">
										<input type="checkbox" name="<?=$field_name?>[]" value="<?=$key?>"<?php if ($selected):?> checked="checked"<?php endif ?><?=$required?>> <?=$label?>
									</label>
								<?php endforeach ?>
							</div>
						<?php break;

						case 'textarea': ?>
							<textarea name="<?=$field_name?>" cols="" rows=""<?=$required?>>
<?=(isset($field['kill_pipes']) && $field['kill_pipes'] === TRUE) ? str_replace('|', NL, $value) : $value?>
</textarea>
						<?php break;

						case 'multi_dropdown': ?>
							<div class="scroll-wrap">
								<?php foreach ($field['choices'] as $field_name => $options): ?>
									<label class="choice block chosen"><?=$options['label']?>
										<?=form_dropdown($field_name, $options['choices'], $options['value'])?>
									</label>
								<?php endforeach ?>
							</div>
						<?php break;

						case 'html': ?>
							<?=$field['content']?>
						<?php endswitch ?>
					<?php endforeach ?>
					<?php if (isset($setting['action_button'])): ?>
						<a class="btn tn action <?=$setting['action_button']['class']?>" href="<?=$setting['action_button']['link']?>"><?=lang($setting['action_button']['text'])?></a>
					<?php endif ?>
					<?php if ($has_note): ?>
						<em><?=$field['note']?></em>
					</div>
					<?php endif ?>
					<?php if ( ! $grid): ?>
						<?=form_error($field_name)?>
					<?php endif ?>
				</div>
			</<?=$element?>>
		<?php endforeach ?>
	<?php endforeach ?>
	<fieldset class="form-ctrls">
		<?php if (isset($buttons)): ?>
			<?php foreach ($buttons as $button): ?>
				<?php
					$class = 'btn';
					$disabled = '';
					$button_text = lang($button['text']);

					if (ee()->form_validation->errors_exist())
					{
						$class = 'btn disable';
						$disabled = 'disabled="disabled"';
						$button_text = lang('btn_fix_errors');
					}
				?>
				<button class="<?=$class?>" <?=$disabled?> name="<?=$button['name']?>" type="<?=$button['type']?>" value="<?=$button['value']?>" data-submit-text="<?=lang($button['text'])?>" data-work-text="<?=lang($button['working'])?>"><?=$button_text?></button>
			<?php endforeach; ?>
		<?php else: ?>
		<?=cp_form_submit($save_btn_text, $save_btn_text_working)?>
		<?php endif; ?>
	</fieldset>
</form>
