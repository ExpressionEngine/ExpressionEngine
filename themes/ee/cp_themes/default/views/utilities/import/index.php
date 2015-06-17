<?php extend_template('default-nav'); ?>

<h1><?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span></h1>
<?=form_open(cp_url('utilities/import-converter'), 'class="settings ajax-validate"')?>
	<?=ee('Alert')->getAllInlines()?>
	<fieldset class="col-group <?=form_error_class('member_file')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('file_location')?> <span class="required" title="required field">&#10033;</span></h3>
			<em><?=lang('file_location_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input class="required" type="text" name="member_file" value="<?=set_value('member_file')?>">
			<?=form_error('member_file')?>
		</div>
	</fieldset>

	<fieldset class="col-group <?=form_error_class('delimiter_special')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('delimiting_char')?></h3>
			<em><?=lang('delimiting_char_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<label class="choice block chosen">
				<input type="radio" name="delimiter" value="comma" <?=set_radio('delimiter', 'comma', TRUE)?>> <?=lang('comma_delimit')?> <i>,</i>
			</label>
			<label class="choice block">
				<input type="radio" name="delimiter" value="tab" <?=set_radio('delimiter', 'tab')?>> <?=lang('tab_delimit')?> <i></i>
			</label>
			<label class="choice block">
				<input type="radio" name="delimiter" value="pipe" <?=set_radio('delimiter', 'pipe')?>> <?=lang('pipe_delimit')?> <i>|</i>
			</label>
			<label class="choice block">
				<input type="radio" name="delimiter" value="other" <?=set_radio('delimiter', 'other')?>> <?=lang('other_delimit')?>
			</label>
			<input type="text" name="delimiter_special" value="<?=set_value('delimiter_special')?>">
			<?=form_error('delimiter')?>
			<?=form_error('delimiter_special')?>
		</div>
	</fieldset>

	<fieldset class="col-group last <?=form_error_class('enclosure')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('enclosing_char')?></h3>
			<em><?=lang('enclosing_char_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input type="text" name="enclosure" value="<?=set_value('enclosure')?>">
			<?=form_error('enclosure')?>
		</div>
	</fieldset>

	<fieldset class="form-ctrls">
		<?=cp_form_submit('import_convert_btn', 'import_convert_btn_saving')?>
	</fieldset>
</form>