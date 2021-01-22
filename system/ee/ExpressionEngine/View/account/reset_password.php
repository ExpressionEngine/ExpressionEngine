<?php $this->extend('_templates/login'); ?>

<div class="login__logo">
    <?php $this->embed('ee:_shared/ee-logo')?>
</div>

<div class="login__content">
	<h1 class="login__title"><?=lang('reset_password')?><span class="icon-reset"></span></h1>
	<?=ee('CP/Alert')->getAllInlines()?>
	<?=form_open(ee('CP/URL')->make('login/reset_password'))?>
		<fieldset>
			<div class="field-instruct">
			<?=lang('new_password', 'password')?>
			</div>
			<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
		</fieldset>
		<fieldset>
			<div class="field-instruct">
			<?=lang('new_password_confirm', 'password_confirm')?>
			</div>
			<?=form_password(array('dir' => 'ltr', 'name' => "password_confirm", 'id' => "password_confirm", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
		</fieldset>
		<fieldset class="last text-center">
			<?=form_hidden('resetcode', $resetcode)?>
			<?=form_submit('submit', lang('change_password'), 'class="button button--primary button--large button--wide" data-work-text="' . lang('updating') . '"')?>
		</fieldset>
	<?=form_close()?>
</div>
