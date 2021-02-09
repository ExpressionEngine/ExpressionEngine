<?php foreach ($profiler_data as $label => $data): ?>
<div class="tab t-<?=$index?> <?=($index == 0) ? 'tab-open' : ''?>">
	<div class="debug-content">
		<h2><?=lang('profiler_' . $label)?></h2>
		<?php if (! is_array($data)): ?>
			<p><?=$data?></p>
		<?php else: ?>
			<ul class="arrow-list">
				<?php foreach ($data as $key => $value): ?>
					<li><b><?=$key?>:</b> <?=($value)?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
<?php endforeach; ?>