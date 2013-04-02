<table id="<?=$field_id?>" class="grid_field_container" cellspacing="0" cellpadding="0">
	<tr>
		<td class="grid_field_container_cell">
			<table class="grid_field" cellspacing="0" cellpadding="0">
				<thead>
					<th class="grid_handle">&nbsp;</th>
					<?php foreach ($columns as $column): ?>
						<th>
							<b><?=$column['col_label']?></b>
							<?php if ( ! empty($column['col_instructions'])): ?>
								<span class="instruction_text">
									<b><?=lang('instructions')?></b> <?=$column['col_instructions']?>
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
								<? if ($column == end($columns)):?>
									<a href="#" class="grid_button_delete" title="<?=lang('grid_delete_row')?>"><?=lang('grid_delete_row')?></a>
								<? endif ?>
								<?=$blank_row['col_id_'.$column['col_id']]?>
							</td>
						<?php endforeach ?>
					</tr>
					<tr class="empty_field">
						<td colspan="<?=count($columns) + 1 ?>" class="empty_field first">
							<?=lang('grid_add_some_data')?>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
		<td class="grid_delete_row_gutter">&nbsp;</td>
	</tr>
	<tr>
		<td>
			<a class="grid_button_add" href="#" title="<?=lang('grid_add_row')?>"><?=lang('grid_add_row')?></a>
		</td>
	</tr>
</table>