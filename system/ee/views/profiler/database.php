<?php foreach ($profiler_data['profiler_duplicate_queries'] as $label => $dupe_queries): ?>
<fieldset>
	<legend><?=lang('profiler_duplicate_queries')?></legend>
		<table>
			<?php foreach ($dupe_queries as $dupe): ?>
				<tr>
					<td><?=$dupe['count']?></td>
					<td><?=$dupe['query']?></td>
				</tr>
			<?php endforeach; ?>
		</table>
</fieldset>
<?php endforeach; ?>

<?php foreach ($profiler_data['profiler_database'] as $label => $dupe_queries): ?>
<fieldset>
	<legend><?=lang('profiler_database')?></legend>
		<table>
			<?php foreach ($dupe_queries as $dupe): ?>
				<tr>
					<td><?=$dupe['time']?></td>
					<td><?=$dupe['query']?></td>
				</tr>
			<?php endforeach; ?>
		</table>
</fieldset>
<?php endforeach; ?>