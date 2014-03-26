<?php extend_template('login'); ?>

<div class="box snap">
	<h1>Reset Password <span class="ico locked"></span></h1>
	<?php if ($message != ''):?>
		<div class="error-msg"><p><b>!!</b> <?=$message?></p></div>
	<?php endif;?>
	<?=form_open('C=login'.AMP.'M=send_reset_token')?>
		<fieldset class="last">
			<?=lang('email_address', 'email')?>
			<?=form_input(array('dir' => 'ltr', 'name' => "email", 'id' => "email", 'maxlength' => 80, 'autocomplete' => 'off'))?>
		</fieldset>
		<fieldset class="form-ctrls">
			<?=form_submit('submit', 'Send Request', 'class="btn" data-work-text="sending..."')?>
		</fieldset>
	<?=form_close()?>
</div>
