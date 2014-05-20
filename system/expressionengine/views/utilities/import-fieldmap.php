<?php extend_template('default-nav'); ?>

<div class="col w-12 last">
	<ul class="breadcrumb">
		<li><a href="<?=cp_url('utilities/import_converter')?>"><?=lang('import_converter')?></a></li>
		<li class="last"><?=lang('assign_fields')?></li>
	</ul>
	<div class="box">
		<h1><?=$cp_page_title?></h1>
		<?=form_open(cp_url('utilities/import_fieldmap_confirm'), 'class="settings"', $form_hidden)?>
			<?php $this->view('_shared/form_messages')?>
			<?php if (form_error('unique_check')): ?>
				<div class="alert inline issue">
					<p><?=form_error('unique_check')?></p>
				</div>
			<?php endif ?>

			<div class="alert inline warn">
				<p><?=lang('import_password_warning')?></p>
			</div>
			<?php
			$i = 0;
			foreach ($fields[0] as $field): ?>
				<fieldset class="col-group">
					<div class="setting-txt col w-8">
						<h3><?=$field?></h3>
					</div>
					<div class="setting-field col w-8 last">
						<?=form_dropdown('field_'.$i, $select_options)?>
					</div>
				</fieldset>
			<?php $i++; endforeach ?>
			<fieldset class="col-group last">
				<div class="setting-txt col w-8">
					<h3><?=lang('plain_text_passwords')?></h3>
					<em><?=lang('plain_text_passwords_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="choice mr chosen yes"><input type="radio" name="encrypt" value="y" checked="checked"> <?=lang('yes')?></label>
					<label class="choice no"><input type="radio" name="encrypt" value="n"> <?=lang('no')?></label>
				</div>
			</fieldset>
			<fieldset class="form-ctrls">
				<?=cp_form_submit('btn_assign_fields', 'btn_assign_fields_working')?>
			</fieldset>
		</form>
	</div>
</div>