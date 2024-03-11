<?php $this->extend('_templates/login'); ?>

	<div class="login__logo">
		<?php if (ee('pro:Access')->hasRequiredLicense() && ee()->config->item('login_logo')) : ?>
		<img src="<?=ee()->config->item('login_logo')?>" alt="Powered by ExpressionEngine&reg;">
		<?php else: ?>
		<?php $this->embed('ee:_shared/ee-logo')?>
		<?php endif; ?>
	</div>

<div class="login__content">
	<h1 class="login__title"><?=$header?></h1>
	<?=ee('CP/Alert')->getAllInlines()?>


	<?=form_open(ee('CP/URL')->make('login/authenticate'), array(), array('return_path' => $return_path, 'after' => $after))?>
		<fieldset>
			<div class="field-instruct">
				<label for="username"><?=lang('username')?> / <?=lang('email')?></label>
			</div>
			<?=form_input(array('dir' => 'ltr', 'name' => "username", 'id' => "username", 'value' => $username, 'maxlength' => USERNAME_MAX_LENGTH, 'tabindex' => 1))?>
		</fieldset>
		<fieldset>
			<div class="field-instruct">
				<label for="password"><?=lang('password')?> &ndash; <a href="<?=ee('CP/URL')->make('/login/forgotten_password_form')?>"><?=lang('remind_me')?></a></label>
			</div>
			<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off', 'tabindex' => 2))?>
		</fieldset>
		<?php if ($cp_session_type == 'c'):?>
		<fieldset>
			<label for="remember_me" class="checkbox-label">
				<input type="checkbox" class="checkbox" name="remember_me" value="1" id="remember_me" tabindex="3">
				<div class="checkbox-label__text"><?=lang('remember_me')?></div>
			</label>
		</fieldset>
		<?php endif;?>
		<fieldset class="last">
			<?=form_submit('submit', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . '" tabindex="4" ' . $btn_disabled)?>
		</fieldset>
	<?=form_close()?>
</div>
