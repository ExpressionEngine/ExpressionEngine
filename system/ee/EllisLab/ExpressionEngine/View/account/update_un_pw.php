<?php $this->extend('_templates/login'); ?>

<div class="box snap">
	<h1><?=lang('new_requirements')?> <span class="ico locked"></span></h1>
	<?php if ($message != ''):?>
		<div class="alert inline <?=$message_status?>">
			<p><?php if ($message_status != 'success'): ?><b>!!</b> <?php endif ?><?=$message?></p>
			<ul>
				<?php foreach ($required_changes as $text): ?>
					<li><?=$text?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif;?>

	<?=form_open(ee('CP/URL')->make('login/update_un_pw'), array(), $hidden)?>
		<?php if ($new_username_required):?>
			<fieldset>
				<?=lang('choose_new_un', 'new_username')?>
				<?=form_input(array('dir' => 'ltr', 'name' => "new_username", 'value'=> $username, 'id' => "new_username", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</fieldset>
		<?php endif;?>
		<?php if ($new_username_required AND ! $new_password_required): ?>
			<fieldset class="last">
				<?=lang('existing_password', 'password')?>
				<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</fieldset>
		<?php endif; ?>
		<?php if ($new_password_required):?>
			<fieldset>
				<?=lang('existing_password', 'password')?>
				<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</fieldset>
			<fieldset>
				<?=lang('choose_new_pw', 'new_password')?>
				<?=form_password(array('dir' => 'ltr', 'name' => "new_password", 'id' => "new_password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</fieldset>
			<fieldset class="last">
				<?=lang('confirm_new_pw', 'confirm_new_pw')?>
				<?=form_password(array('dir' => 'ltr', 'name' => "confirm_new_pw", 'id' => "confirm_new_pw", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</fieldset>
		<?php endif; ?>
		<fieldset class="form-ctrls">
			<?=form_submit('submit', lang('update'), 'class="btn" data-work-text="updating..."')?>
		</fieldset>
	<?=form_close()?>
</div>
