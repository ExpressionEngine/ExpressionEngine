<div class="panel warn">
	<div class="panel-heading" style="text-align: center;">
		<h3><?=$title?></h3>
	</div>
	<div class="panel-body">
		<div class="updater-msg">
			<p style="margin-bottom: 20px;"><?=lang('error_occurred')?></p>

			<div class="alert alert--attention app-notice---error">
				<div class="alert__icon">
					<i class="fas fa-info-circle fa-fw"></i>
				</div>
				<div class="alert__content">
					<?=$error?>
				</div>
			</div>
		</div>
	</div>
	<div class="panel-footer">
		<p class="msg-choices">
			<a href="#" onclick="location.reload()" class="button button--primary"><?=lang('retry')?></a>
		</p>
	</div>
</div>
