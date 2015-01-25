<div class="alert <?=$alert->type?> <?=$alert->severity?>">
	<?php if ($alert->title): ?>
	<h3><?=$alert->title?></h3>
	<?php endif; ?>
	<?=$alert->body?>
	<?php if ($alert->sub_alert): ?>
	<div class="alert <?=$alert->sub_alert->type?> <?=$alert->sub_alert->severity?>">
		<?=$alert->sub_alert->body?>
	</div>
	<?php endif; ?>
	<?php if ($alert->has_close_button): ?>
	<a class="close" href=""></a>
	<?php endif; ?>
</div>