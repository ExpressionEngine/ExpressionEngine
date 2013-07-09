<table id="<?=$field_id?>" class="grid_field_container" cellspacing="0" cellpadding="0">
	<tr>
		<td class="grid_field_container_cell">
			<table class="grid_field" cellspacing="0" cellpadding="0">
				<thead>
					<th class="grid_handle">&nbsp;</th>
					<?php foreach ($columns as $column): ?>
						<th width="<?=$column['col_width']?>%">
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
						<tr class="grid_row">
							<td class="grid_handle">&nbsp;</td>
							<?php foreach ($columns as $column): ?>
								<td width="<?=$column['col_width']?>%"
									data-fieldtype="<?=$column['col_type']?>"
									data-column-id="<?=$column['col_id']?>"
									data-row-id="<?=$row['row_id']?>">

									<div class="grid_cell">
										<?php if ($column == end($columns)):?>
											<a href="#" class="grid_button_delete" tabindex="-1" title="<?=lang('grid_delete_row')?>"><?=lang('grid_delete_row')?></a>
										<?php endif ?>
										<?=$row['col_id_'.$column['col_id']]?>
										<?php if (isset($row['col_id_'.$column['col_id'].'_error'])): ?>
											<p class="grid_error"><?=$row['col_id_'.$column['col_id'].'_error']?></p>
										<?php endif ?>
									</div>

								</td>
							<?php endforeach ?>
						</tr>
					<?php endforeach ?>
					<tr class="grid_row blank_row">
						<td class="grid_handle">&nbsp;</td>
						<?php foreach ($columns as $column): ?>
							<td width="<?=$column['col_width']?>%"
								data-fieldtype="<?=$column['col_type']?>"
								data-column-id="<?=$column['col_id']?>">

								<div class="grid_cell">
									<?php if ($column == end($columns)):?>
										<a href="#" class="grid_button_delete" tabindex="-1" title="<?=lang('grid_delete_row')?>"><?=lang('grid_delete_row')?></a>
									<?php endif ?>
									<?=$blank_row['col_id_'.$column['col_id']]?>
								</div>

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