
<?php foreach ($profiler_data as $label => $data): ?>
<fieldset id="expressionengine_profiler_<?=$label?>">
	<legend><?=lang('profiler_'.$label)?>
		(<span onclick="var s=document.getElementById('expressionengine_profiler_<?=$label?>_data').style;s.display=s.display=='none'?'':'none';this.innerHTML=this.innerHTML=='Show'?'Hide':'Show';">Hide</span>)
	</legend>
	<?php if ( ! is_array($data)): ?>
		<p id="expressionengine_profiler_<?=$label?>_data"><?=$data?></p>
	<?php else: ?>
		<table id="expressionengine_profiler_<?=$label?>_data">
			<?php foreach ($data as $key => $value): ?>
				<tr>
					<td><?=$key?></td>
					<td><?=($value)?:'&nbsp;'?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</fieldset>
<?php endforeach; ?>