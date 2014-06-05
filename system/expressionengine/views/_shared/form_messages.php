<?php foreach (ee()->view->form_messages as $class => $message): ?>
	<div class="alert inline <?=$class?>">
		<h3><?=$message['title']?></h3>
		<?php if ( ! empty($message['description'])): ?>
			<p><?=$message['description']?></p>
		<?php endif ?>
	</div>
<?php endforeach; ?>