<?php $this->extend('_templates/login'); ?>

<div class="login__logo">
    <?php $this->embed('ee:_shared/ee-logo')?>
</div>

<div class="login__content reset-password">
<h1 class="login__title"><?=lang('new_requirements')?> <i class="fal fa-redo-alt"></i></h1>
	<?=ee('CP/Alert')->getAllInlines()?>

	<?=form_open(ee('CP/URL')->make('login/update_un_pw'), array(), $hidden)?>
		<?php if ($new_username_required):?>
			<fieldset>
				<div class="field-instruct">
				<?=lang('choose_new_un', 'new_username')?>
				</div>
				<?=form_input(array('dir' => 'ltr', 'name' => "new_username", 'value' => $username, 'id' => "new_username", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</fieldset>
		<?php endif;?>
		<?php if ($new_username_required and ! $new_password_required): ?>
			<fieldset>
				<div class="field-instruct">
				<?=lang('existing_password', 'password')?>
				</div>
				<div class="field-control" style="position: relative;">
					<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'current-password'))?>
				</div>
			</fieldset>
		<?php endif; ?>
		<?php if ($new_password_required):?>
			<fieldset>
				<div class="field-instruct">
				<?=lang('existing_password', 'verify_password')?>
				</div>
				<div class="field-control" style="position: relative;">
					<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'current-password'))?>
				</div>
			</fieldset>
			<fieldset>
				<div class="field-instruct">
				<?=lang('choose_new_pw', 'new_password')?>
				</div>
				<div class="field-control" style="position: relative;">
					<?=form_password(array('dir' => 'ltr', 'name' => "new_password", 'id' => "new_password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
				</div>
			</fieldset>
			<fieldset>
				<div class="field-instruct">
				<?=lang('confirm_new_pw', 'new_password_confirm')?>
				</div>
				<div class="field-control" style="position: relative;">
					<?=form_password(array('dir' => 'ltr', 'name' => "new_password_confirm", 'id' => "new_password_confirm", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
				</div>
			</fieldset>
		<?php endif; ?>
		<fieldset class="last text-center">
			<?=form_submit('submit', lang('update'), 'class="button button--primary button--large button--wide" data-work-text="' . lang('updating') . '"')?>
		</fieldset>
	<?=form_close()?>
</div>
