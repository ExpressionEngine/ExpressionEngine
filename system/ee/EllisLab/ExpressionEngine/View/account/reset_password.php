<?php $this->extend('_templates/login'); ?>

<div class="box snap">
	<h1><?=lang('new_password')?> <span class="ico locked"></span></h1>
	<?php if ( ! empty($messages)):?>
		<div class="alert inline <?=$message_status?>">
			<?php foreach ($messages as $message): ?>
				<p><?php if ($message_status != 'success'): ?><b>!!</b> <?php endif ?><?=$message?></p>
			<?php endforeach ?>
		</div>
	<?php endif;?>
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
