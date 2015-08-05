<?php foreach ($profiler_data as $label => $data): ?>
<fieldset>
	<legend><?=lang($label)?></legend>
	<?php if ( ! is_array($data)): ?>
		<p><?=$data?></p>
	<?php else: ?>
		<table>
			<?php foreach ($data as $key => $value): ?>
				<tr>
					<td><?=$key?></td>
					<td><?=$value?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</fieldset>
<?php endforeach; ?>