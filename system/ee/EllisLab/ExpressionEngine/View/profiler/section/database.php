<div class="tab t-<?=$index?>">
	<div class="debug-content">
		<?php foreach ($profiler_data['duplicate_queries'] as $label => $dupe_queries): ?>
			<h2><?=lang('profiler_duplicate_queries')?> (<?=$label?>)</h2>
			<?php if (count($dupe_queries) == 0): ?>
				<div class="no-results"><?=lang('profiler_no_duplicate_queries')?></div>
			<?php else: ?>
				<ul class="query-list">
					<?php foreach ($dupe_queries as $dupe): ?>
						<li>
							<div class="query-time"><?=$dupe['count']?> &times;</div>
							<div class="query-wrap"><?=$dupe['query']?></div>
							<div class="query-file"><?=$dupe['location']?></div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php foreach ($profiler_data['database'] as $label => $queries): ?>
			<h2><?=lang('profiler_queries')?> (<?=$label?>)</h2>
			<ul class="query-list">
				<?php foreach ($queries as $query): ?>
					<li>
						<div class="query-time"><?=$query['time']?></div>
						<div class="query-wrap"><?=$query['query']?></div>
						<div class="query-file"><?=$query['location']?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</div>
</div>