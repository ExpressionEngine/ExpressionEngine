<?php extend_template('default-nav'); ?>

<div class="col w-12 last">
	<div class="box">
		<h1><?=$cp_page_title?></h1>
		<form class="settings">
			<?=form_open(cp_url('utilities/import_converter'), 'class="settings ajax-validate"')?>
			<?php $this->view('_shared/form_messages')?>
			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=lang('file_location')?></h3>
					<em><?=lang('file_location_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="text" value="">
				</div>
			</fieldset>

			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=lang('delimiting_char')?></h3>
					<em><?=lang('delimiting_char_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="choice block chosen">
						<input type="checkbox" checked="checked"> <?=lang('comma_delimit')?> <i>,</i>
					</label>
					<label class="choice block">
						<input type="checkbox"> <?=lang('tab_delimit')?> <i></i>
					</label>
					<label class="choice block">
						<input type="checkbox"> <?=lang('pipe_delimit')?> <i>|</i>
					</label>
					<label class="choice block">
						<input type="checkbox"> <?=lang('other_delimit')?>
					</label>
					<input type="text" value="">
				</div>
			</fieldset>

			<fieldset class="col-group last">
				<div class="setting-txt col w-8">
					<h3><?=lang('enclosing_char')?></h3>
					<em><?=lang('enclosing_char_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="text" value="">
				</div>
			</fieldset>

			<fieldset class="form-ctrls">
				<?=cp_form_submit('import_convert_btn', 'import_convert_btn_working')?>
			</fieldset>
		</form>
	</div>
</div>