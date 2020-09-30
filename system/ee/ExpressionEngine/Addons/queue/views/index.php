<div class="tab-wrap">
	<div class="tab-bar">
		<div class="tab-bar__tabs">
			<button type="button" class="tab-bar__tab js-tab-button active" rel="t-0"><?=lang('queue_jobs')?></button>
			<button type="button" class="tab-bar__tab js-tab-button" rel="t-1"><?=lang('queue_failed_jobs')?></button>
		</div>
	</div>
	<div class="tab t-0 tab-open">
		<?php $this->embed('ee:_shared/table', $jobs_table); ?>
	</div>
	<div class="tab t-1">
		<?php $this->embed('ee:_shared/table', $failed_jobs_table); ?>
	</div>
</div>
