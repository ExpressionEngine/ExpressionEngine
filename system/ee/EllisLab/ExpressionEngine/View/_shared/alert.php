<div class="app-notice app-notice--<?=$alert->type?> app-notice---<?=$alert->severity?>">
	<div class="app-notice__tag">
		<span class="app-notice__icon"></span>
	</div>
	<div class="app-notice__content">
		<?php if ($alert->title): ?>
			<p><b><?=$alert->title?></b></p>
		<?php endif; ?>

		<?=$alert->body?>

		<?php if ($alert->sub_alert): ?>
			<div class="app-notice app-notice--<?=$alert->sub_alert->type?> app-notice---<?=$alert->sub_alert->severity?>">
				<div class="app-notice__tag">
					<span class="app-notice__icon"></span>
				</div>
				<div class="app-notice__content">
					<?=$alert->sub_alert->body?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php if ($alert->has_close_button): ?>
		<div class="app-notice__controls">
			<a href="#" class="app-notice__dismiss js-notice-dismiss"></a>
		</div>
	<?php endif; ?>
</div>
