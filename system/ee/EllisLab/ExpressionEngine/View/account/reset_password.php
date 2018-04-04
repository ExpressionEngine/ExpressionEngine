<?php $this->extend('_templates/login'); ?>

<div class="box">
	<h1><?=lang('reset_password')?><span class="icon-reset"></span></h1>
	<?=ee('CP/Alert')->getAllInlines()?>
	<?=form_open(ee('CP/URL')->make('login/reset_password'))?>
		<fieldset>
			<?=lang('new_password', 'password')?>
			<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
		</fieldset>
		<fieldset class="last">
			<?=lang('new_password_confirm', 'password_confirm')?>
			<?=form_password(array('dir' => 'ltr', 'name' => "password_confirm", 'id' => "password_confirm", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
		</fieldset>
		<fieldset class="form-ctrls">
			<?=form_hidden('resetcode', $resetcode)?>
			<?=form_submit('submit', 'Change Password', 'class="btn" data-work-text="updating..."')?>
		</fieldset>
	<?=form_close()?>
</div>
