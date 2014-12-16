<div class="alert <?=$alert->type?> <?=$alert->severity?>">
	<p><?=$alert->description?></p>
	<?php if (count($alert->list)): ?>
	<ul>
		<?php foreach ($alert->list as $item): ?>
		<li><?=$item?></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	<?php if ($alert->sub_alert): ?>
		<div class="alert <?=$alert->sub_alert->type?> <?=$alert->sub_alert->severity?>">
			<p><?=$alert->sub_alert->description?></p>
			<?php if (count($alert->sub_alert->list)): ?>
			<ul>
				<?php foreach ($alert->sub_alert->list as $item): ?>
				<li><?=$item?></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>