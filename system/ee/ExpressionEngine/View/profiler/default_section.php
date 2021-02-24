
<?php foreach ($profiler_data as $label => $data): ?>
<div class="tab t-<?=$index?>">
	<div class="debug-content">
		<h2><?=lang('profiler_' . $label)?></h2>

		<?php if (! is_array($data)): ?>
			<p><?=$data?></p>
		<?php else: ?>
			<dl>
				<?php foreach ($data as $key => $value): ?>
					<dt><code><?=$key?></code></dt>
					<dd><?=($value) ?: '&nbsp;'?></dd>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</div>
<?php endforeach; ?>