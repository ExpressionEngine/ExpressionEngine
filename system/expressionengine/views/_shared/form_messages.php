<?php foreach ($form_messages as $class => $message): ?>
	<div class="alert inline <?=$class?>">
		<h3><?=lang('cp_message_'.$class)?></h3>
		<?php if (is_array($message)): ?>
			<p><?=implode('<br>', $message)?></p>
		<?php else: ?>
			<p><?=$message?></p>
		<?php endif ?>
	</div>
<?php endforeach; ?>