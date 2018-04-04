<?php $this->extend('_templates/login'); ?>

<div class="box snap">
	<h1><?=lang('reset_password')?> <span class="icon-reset"></span></h1>
	<?=ee('CP/Alert')->getAllInlines()?>
	<?=form_open(ee('CP/URL')->make('/cp/login/send_reset_token'))?>
		<fieldset class="last">
			<?=lang('email_address', 'email')?>
			<?=form_input(array('dir' => 'ltr', 'name' => "email", 'id' => "email", 'maxlength' => 80, 'autocomplete' => 'off', 'tabindex' => 1))?>
		</fieldset>
		<fieldset class="form-ctrls">
			<?=form_submit('submit', 'Send Request', 'class="btn" data-work-text="sending..." tabindex="2"')?>
		</fieldset>
	<?=form_close()?>
</div>
