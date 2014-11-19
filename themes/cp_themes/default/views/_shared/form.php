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
}?>
<?=form_open($base_url, 'class="'.$form_class.'"', (isset($form_hidden)) ? $form_hidden : array())?>
	<?php $this->view('_shared/alerts')?>
	<?php foreach ($sections as $name => $settings): ?>
		<?php if (is_string($name)): ?>
			<h2><?=lang($name)?></h2>
		<?php endif ?>
		<?php foreach ($settings as $setting): ?>
			<?php
			$last_class = ($setting == end($settings)) ? ' last' : ''; ?>
			<fieldset class="col-group<?=$last_class?> <?=form_error_class(array_keys($setting['fields']))?> <?=(isset($setting['grid']) && $setting['grid'] == TRUE) ? 'grid-publish' : ''?>">
				<div class="setting-txt col <?=(isset($setting['wide']) && $setting['wide'] == TRUE) ? 'w-16' : 'w-8'?>">
					<?php foreach ($setting['fields'] as $field_name => $field)
					{
						if ($required = (isset($field['required']) && $field['required'] == TRUE))
						{
							break;
						}
					}
					$security = (isset($setting['security']) && $setting['security'] == TRUE);
					?>
					<h3<?php if ($security):?> class="enhance"<?php endif ?>><?=lang($setting['title'])?><?php if ($required): ?> <span class="required" title="required field">&#10033;</span><?php endif ?><?php if ($security): ?> <span title="enhance security"></span><?php endif ?></h3>
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
						?>

						<?php switch ($field['type']):
						case 'text': ?>
							<input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$required?>>
						<?php break;
						case 'password': ?>
							<input type="password" name="<?=$field_name?>"<?=$required?>>
						<?php break;
						case 'hidden': ?>
							<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
						<?php break;

						case 'inline_radio': ?>
							<?php foreach ($field['choices'] as $key => $label):
								$checked = ($key == $value); ?>
								<label class="choice mr <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$required?>> <?=lang($label)?></label>
							<?php endforeach ?>
						<?php break;

						case 'yes_no': ?>
							<label class="choice mr<?php if ($value == 'y'):?> chosen<?php endif ?> yes"><input type="radio" name="<?=$field_name?>" value="y"<?php if ($value == 'y'):?> checked="checked"<?php endif ?><?=$required?>> yes</label>
							<label class="choice <?php if ($value == 'n'):?> chosen<?php endif ?> no"><input type="radio" name="<?=$field_name?>" value="n"<?php if ($value == 'n'):?> checked="checked"<?php endif ?><?=$required?>> no</label>
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

						case 'html': ?>
							<?=$field['content']?>
						<?php endswitch ?>
					<?php endforeach ?>
					<?php if (isset($setting['action_button'])): ?>
						<a class="btn tn action <?=$setting['action_button']['class']?>" href="<?=$setting['action_button']['link']?>"><?=lang($setting['action_button']['text'])?></a>
					<?php endif ?>
					<?=form_error($field_name)?>
				</div>
			</fieldset>
		<?php endforeach ?>
	<?php endforeach ?>
	<fieldset class="form-ctrls">
		<?=cp_form_submit($save_btn_text, $save_btn_text_working)?>
	</fieldset>
</form>
