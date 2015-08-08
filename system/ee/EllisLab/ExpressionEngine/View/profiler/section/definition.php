<div class="tab t-<?=$index?> <?=($index==0)?'tab-open':''?>">
	<div class="debug-content">
	<?php foreach ($profiler_data as $dataset): ?>
		<?php foreach ($dataset as $label => $data): ?>
			<h2><?=lang('profiler_'.$label)?></h2>
			<?php if (empty($data)): ?>
				<div class="no-results"><?=sprintf(lang('profiler_no_variables'), lang('profiler_'.$label))?></div>
			<?php else: ?>
				<dl>
					<?php foreach ($data as $key => $value): ?>
						<dt><code><?=$key?>:</code></dt>
						<dd><?=($value)?:'&nbsp;'?></dd>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endforeach; ?>
	</div>
</div>
