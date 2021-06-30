<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="form-standard">
	<?=form_open(ee('CP/URL')->make('utilities/import-converter/import-fieldmap-confirm'), '', $form_hidden)?>
		<div class="form-btns form-btns-top">
			<h1><?=$cp_page_title?></h1>
		</div>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if (form_error('unique_check')): ?>
			<?=ee('CP/Alert')
		    ->makeInline()
		    ->asIssue()
		    ->withTitle(lang('file_not_converted'))
		    ->addToBody(form_error('unique_check'))
		    ->render()?>
		<?php endif ?>

		<?=ee('CP/Alert')
		    ->makeInline()
		    ->asImportant()
		    ->addToBody(lang('import_password_warning'))
		    ->render()?>

		<?php
        $i = 0;
        foreach ($fields[0] as $field): ?>
			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=ee('Security/XSS')->clean($field)?></h3>
				</div>
				<div class="setting-field col w-8 last">
					<?=form_dropdown('field_' . $i, $select_options, set_value('field_' . $i, ''))?>
				</div>
			</fieldset>
		<?php $i++; endforeach ?>
		<fieldset class="col-group last">
			<div class="setting-txt col w-8">
				<h3><?=lang('plain_text_passwords')?></h3>
				<em><?=lang('plain_text_passwords_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<label class="choice mr chosen yes"><input type="radio" name="encrypt" value="y" <?=set_radio('encrypt', 'y')?> <?php if (! isset($_POST['encrypt'])):?> checked="checked"<?php endif ?>> <?=lang('yes')?></label>
				<label class="choice no"><input type="radio" name="encrypt" value="n" <?=set_radio('encrypt', 'n')?>> <?=lang('no')?></label>
			</div>
		</fieldset>
		<div class="form-btns">
			<?=cp_form_submit('btn_assign_fields', 'btn_saving')?>
		</div>
	</form>
</div>
