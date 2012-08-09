<?php extend_template('default') ?>

<?=form_open('C=tools_communicate'.AMP.'M=delete_emails', '', $hidden)?>
	
	<p class="notice"><?=lang('delete_question')?></p>
	
	<p>
	<?php foreach ($emails as $email): ?>
	<?=$email?><br />
	<?php endforeach; ?>
	</p>
	
	<p><?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?></p>

<?=form_close()?>