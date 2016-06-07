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
							<div class="query-wrap"><pre><code><?=htmlspecialchars($dupe['query'], ENT_QUOTES, 'UTF-8')?></code></pre></div>
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
					<?php $warn = ($query['memory'] > $query['memory_threshold'] OR $query['time'] > $query['time_threshold']) ? 'class="debug-warn"' : ''; ?>
					<li <?=$warn?>>
						<div class="query-time">
							<?=$query['time']?>s
							<i><?=$query['formatted_memory']?></i>
						</div>
						<div class="query-wrap"><pre><code class="sql"><?=htmlspecialchars($query['query'], ENT_QUOTES, 'UTF-8')?></code></pre></div>
						<div class="query-file"><?=$query['location']?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</div>
</div>
