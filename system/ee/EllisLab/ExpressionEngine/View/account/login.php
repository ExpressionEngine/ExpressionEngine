<?php $this->extend('_templates/login'); ?>

<div class="box snap">
	<h1><?=$header?> <span class="ico locked"></span></h1>
	<?php if ($message != ''):?>
		<div class="alert inline <?=$message_status?>">
			<p><?php if ($message_status != 'success'): ?><b>!!</b> <?php endif ?><?=$message?></p>
		</div>
	<?php endif;?>
	<?=form_open(ee('CP/URL')->make('login/authenticate'), array(), array('return_path' => $return_path))?>
		<fieldset>
			<?=lang('username', 'username')?>
			<?=form_input(array('dir' => 'ltr', 'name' => "username", 'id' => "username", 'value' => $username, 'maxlength' => 50))?>
		</fieldset>
		<fieldset class="last">
			<?=lang('password', 'password')?>
			<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			<em><a href="<?=ee('CP/URL')->make('/login/forgotten_password_form')?>"><?=lang('forgotten_password')?></a></em>
		</fieldset>
		<?php if ($cp_session_type == 'c'):?>
		<fieldset class="options">
			<label for="remember_me"><input type="checkbox" name="remember_me" value="1" id="remember_me"> <?=lang('remember_me')?></label>
		</fieldset>
		<?php endif;?>
		<fieldset class="form-ctrls">
			<?=form_submit('submit', $btn_label, 'class="'.$btn_class.'" data-work-text="authenticating..." '.$btn_disabled)?>
		</fieldset>
	<?=form_close()?>
</div>
