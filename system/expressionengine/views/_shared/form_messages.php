<?php foreach ($form_messages as $class => $message): ?>
	<div class="alert inline <?=$class?>">
		<h3><?=lang('cp_message_'.$class)?></h3>
		<p><?=$message?></p>
	</div>
<?php endforeach; ?>