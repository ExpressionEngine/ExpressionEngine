<?php if (isset(ee()->view->alerts['inline'])): ?>
	<?php foreach (ee()->view->alerts['inline'] as $alert): ?>
		<div class="alert inline <?=$alert['type']?>">
			<h3><?=$alert['title']?></h3>
			<?php if ( ! empty($alert['description'])): ?>
				<p><?=$alert['description']?></p>
			<?php endif ?>
		</div>
	<?php endforeach; ?>
<?php endif ?>