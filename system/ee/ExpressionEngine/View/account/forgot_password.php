<?php $this->extend('_templates/login'); ?>

<div class="login__logo">
    <?php $this->embed('ee:_shared/ee-logo')?>
</div>

<div class="login__content">
	<h1 class="login__title"><?=lang('reset_password')?> </h1>

	<?=ee('CP/Alert')->getAllInlines()?>

	<?=form_open(ee('CP/URL')->make('/cp/login/send_reset_token'))?>
		<fieldset>
			<div class="field-instruct">
			<?=lang('email_address', 'email')?>
			</div>
			<?=form_input(array('dir' => 'ltr', 'name' => "email", 'id' => "email", 'maxlength' => 80, 'autocomplete' => 'off', 'tabindex' => 1))?>
		</fieldset>
		<fieldset class="last text-center">
			<?=form_submit('submit', lang('send_request'), 'class="button button--primary button--large button--wide" data-work-text="' . lang('sending') . '" tabindex="2"')?>
		</fieldset>
	<?=form_close()?>
</div>
