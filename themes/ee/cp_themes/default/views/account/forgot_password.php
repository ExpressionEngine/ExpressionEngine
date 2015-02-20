<?php extend_template('login'); ?>

<div class="box snap">
	<h1><?=lang('reset_password')?> <span class="ico locked"></span></h1>
	<?php if ($message != ''):?>
		<div class="alert inline <?=$message_status?>"><p><b>!!</b> <?=$message?></p></div>
	<?php endif;?>
	<?=form_open(cp_url('/cp/login/send_reset_token'))?>
		<fieldset class="last">
			<?=lang('email_address', 'email')?>
			<?=form_input(array('dir' => 'ltr', 'name' => "email", 'id' => "email", 'maxlength' => 80, 'autocomplete' => 'off'))?>
		</fieldset>
		<fieldset class="form-ctrls">
			<?=form_submit('submit', 'Send Request', 'class="btn" data-work-text="sending..."')?>
		</fieldset>
	<?=form_close()?>
</div>
