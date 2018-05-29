<?php $this->extend('_templates/login'); ?>

<div class="box">
	<h1><b><?=$header?></b><span class="icon-locked"></span></h1>
	<?=ee('CP/Alert')->getAllInlines()?>
	<?=form_open(ee('CP/URL')->make('login/authenticate'), array(), array('return_path' => $return_path))?>
		<fieldset>
			<?=lang('username', 'username')?>
			<?=form_input(array('dir' => 'ltr', 'name' => "username", 'id' => "username", 'value' => $username, 'maxlength' => USERNAME_MAX_LENGTH, 'tabindex' => 1))?>
		</fieldset>
		<fieldset class="last">
			<label for="password"><?=lang('password')?> &ndash; <a href="<?=ee('CP/URL')->make('/login/forgotten_password_form')?>"><?=lang('remind_me')?></a></label>
			<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off', 'tabindex' => 2))?>
		</fieldset>
		<?php if ($cp_session_type == 'c'):?>
		<fieldset class="options">
			<label for="remember_me"><input type="checkbox" name="remember_me" value="1" id="remember_me" tabindex="3"> <?=lang('remember_me')?></label>
		</fieldset>
		<?php endif;?>
		<fieldset class="form-ctrls">
			<?=form_submit('submit', $btn_label, 'class="'.$btn_class.'" data-work-text="authenticating..." tabindex="4" '.$btn_disabled)?>
		</fieldset>
	<?=form_close()?>
</div>
