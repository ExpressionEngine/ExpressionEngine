<?php extend_template('default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<?=form_open(cp_url('utilities/member_import'), 'class="settings ajax-validate"')?>
	<?php $this->view('_shared/form_messages')?>
	<fieldset class="col-group last <?=form_error_class('xml_file')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('mbr_xml_file')?></h3>
			<em><?=lang('mbr_xml_file_location')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input name="xml_file" type="text" value="<?=set_value('xml_file')?>">
			<?=form_error('xml_file')?>
		</div>
	</fieldset>

	<h2><?=lang('mbr_import_default_options')?></h2>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?=lang('member_group')?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8 last">
			<?=form_dropdown('group_id', $member_groups, set_value('group_id'))?>
		</div>
	</fieldset>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?=lang('mbr_language')?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8 last">
			<?=form_dropdown('language', $language_options, set_value('language'))?>
		</div>
	</fieldset>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?=lang('mbr_timezone')?></h3>
			<em><?=lang('mbr_timezone_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?=$timezone_menu?>
		</div>
	</fieldset>

	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?=lang('mbr_datetime_fmt')?></h3>
			<em><?=lang('mbr_datetime_fmt_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?=form_preference('date_format', $date_format)?>
			<?=form_preference('time_format', $time_format)?>
		</div>
	</fieldset>

	<fieldset class="col-group last">
		<div class="setting-txt col w-8">
			<h3><?=lang('mbr_create_custom_fields')?></h3>
			<em><?=lang('mbr_create_custom_fields_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<label class="choice mr chosen yes"><input type="radio" name="auto_custom_field" value="y" <?=set_radio('auto_custom_field', 'y', TRUE)?>> <?=lang('yes')?></label>
			<label class="choice no"><input name="auto_custom_field" value="n" type="radio" <?=set_radio('auto_custom_field', 'n')?>> <?=lang('no')?></label>
		</div>
	</fieldset>

	<fieldset class="form-ctrls">
		<?=cp_form_submit('mbr_import_btn', 'mbr_import_btn_working')?>
	</fieldset>
</form>