<?php extend_template('default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<?=form_open($base_url, 'class="settings"')?>
	<?php $this->view('_shared/alerts')?>
	<?php foreach ($sections as $name => $settings): ?>
		<?php if (is_string($name)): ?>
			<h2><?=lang($name)?></h2>
		<?php endif ?>
		<?php foreach ($settings as $setting): ?>
			<?php
			$last_class = ($setting == end($settings)) ? ' last' : ''; ?>
			<fieldset class="col-group<?=$last_class?>">
				<div class="setting-txt col w-8">
					<h3><?=lang($setting['title'])?></h3>
					<em><?=lang($setting['desc'])?></em>
				</div>
				<div class="setting-field col w-8 last">
					<?php foreach ($setting['fields'] as $field_name => $field):
						// Get the value of the field
						$value = isset($field['value']) ? $field['value'] : ee()->config->item($field_name); ?>

						<?php switch ($field['type']):
						case 'text': ?>
							<input type="text" name="<?=$field_name?>" value="<?=$value?>">
						<?php break;

						case 'inline_radio': ?>
							<?php foreach ($field['choices'] as $key => $label):
								$checked = ($key == $value); ?>
								<label class="choice mr <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?>> <?=lang($label)?></label>
							<?php endforeach ?>
						<?php break;

						case 'dropdown': ?>
							<?=form_dropdown($field_name, $field['choices'], $value)?>
						<?php break;

						case 'html': ?>
							<?=$field['content']?>
						<?php endswitch ?>
					<?php endforeach ?>
					<?php if (isset($setting['action_button'])): ?>
						<a class="btn tn action <?=$setting['action_button']['class']?>" href="<?=$setting['action_button']['link']?>"><?=lang($setting['action_button']['text'])?></a>
					<?php endif ?>
				</div>
			</fieldset>
		<?php endforeach ?>
	<?php endforeach ?>
	<fieldset class="form-ctrls">
		<?=cp_form_submit($save_btn_text, $save_btn_text_working)?>
	</fieldset>
</form>