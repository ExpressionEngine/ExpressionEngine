<table id="<?=$field_id?>" class="grid_field" cellspacing="0" cellpadding="0">
	<thead>
		<th class="grid_handle">&nbsp;</th>
		<?php foreach ($columns as $column): ?>
			<th>
				<b><?=$column['col_label']?></b>
				<?php if ( ! empty($column['col_instructions'])): ?>
					<span class="instruction_text">
						<b>Instructions:</b> <?=$column['col_instructions']?>
					</span>
				<?php endif ?>
			</th>
		<?php endforeach ?>
	</thead>
	<tbody class="grid_row_container">
		<?php foreach ($rows as $row): ?>
			<tr>
				<td class="grid_handle">&nbsp;</td>
				<?php foreach ($columns as $column): ?>
					<td width="33%">
						<?=$row['col_id_'.$column['col_id']]?>
					</td>
				<?php endforeach ?>
			</tr>
		<?php endforeach ?>
		<tr class="blank_row">
			<td class="grid_handle">&nbsp;</td>
			<?php foreach ($columns as $column): ?>
				<td width="33%">
					<?=$blank_row['col_id_'.$column['col_id']]?>
				</td>
			<?php endforeach ?>
		</tr>
	</tbody>
</table>

<a class="<?=$field_id?> grid_button_add" href="#">Add Row</a>