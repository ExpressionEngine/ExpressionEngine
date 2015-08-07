<?php foreach ($profiler_data['duplicate_queries'] as $label => $dupe_queries): ?>
<fieldset id="expressionengine_profiler_duplicate_queries">
	<legend><?=lang('profiler_duplicate_queries')?>
		(<span onclick="var s=document.getElementById('expressionengine_profiler_<?=$label?>_data').style;s.display=s.display=='none'?'':'none';this.innerHTML=this.innerHTML=='Show'?'Hide':'Show';">Hide</span>)
	</legend>
		<?php if (count($dupe_queries) == 0): ?>
			<p id="expressionengine_profiler_<?=$label?>_data"><?=lang('profiler_no_duplicate_queries')?></p>
		<?php else: ?>
			<table id="expressionengine_profiler_<?=$label?>_data">
				<?php foreach ($dupe_queries as $dupe): ?>
					<tr>
						<td><?=$dupe['count']?></td>
						<td><?=$dupe['query']?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
</fieldset>
<?php endforeach; ?>

<?php foreach ($profiler_data['database'] as $label => $dupe_queries): ?>
<fieldset id="expressionengine_profiler_database">
	<legend><?=lang('profiler_database')?>: <?=$label?>
			(<span onclick="var s=document.getElementById('expressionengine_profiler_<?=$label?>_data').style;s.display=s.display=='none'?'':'none';this.innerHTML=this.innerHTML=='Show'?'Hide':'Show';">Show</span>)
	</legend>
		<table id="expressionengine_profiler_<?=$label?>_data" style="display:none;">
			<?php foreach ($dupe_queries as $dupe): ?>
				<tr>
					<td><?=$dupe['time']?></td>
					<td><?=$dupe['query']?></td>
				</tr>
			<?php endforeach; ?>
		</table>
</fieldset>
<?php endforeach; ?>