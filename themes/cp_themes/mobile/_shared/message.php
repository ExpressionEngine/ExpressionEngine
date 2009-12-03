<?php foreach ($cp_messages as $cp_message_type => $cp_message):?>
	<?php if ($cp_message != ''):?>
	<div class="container pad">
	<p><?=$cp_message?></p>
	</div>
	<?php endif; ?>
<?php endforeach;

/* End of file message.php */
/* Location: ./themes/cp_themes/mobile/_shared/message.php */