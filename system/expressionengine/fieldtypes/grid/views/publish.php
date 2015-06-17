<div class="tbl-wrap">
	<table id="<?=$field_id?>" class="grid-input-form" cellespacing="0">
		<tr>
			<th class="first reorder-col"<?php if (empty($rows)) echo $hide?>></th>
			<?php
			$first = current($columns);
			$last = end($columns);
			reset($columns);
			foreach ($columns as $column):
				$class = '';

				if (empty($rows))
				{
					if ($column == $first)
					{
						$class = 'first';
					}
					elseif ($column == $last)
					{
						$class = 'last';
					}
				}

				if ($column['col_type'] == 'rte')
				{
					$class .= ' grid-rte';
				}

				if ($column['col_type'] == 'relationship'
					&& $column['col_settings']['allow_multiple'])
				{
					$class .= ' grid-mr';
				}

				if ($class)
				{
					$class = ' class="' . trim($class) . '"';
				}
			?>
			<th<?=$class?>><?=$column['col_label']?><?php if ( ! empty($column['col_instructions'])): ?> <em class="grid-instruct"><?=$column['col_instructions']?></em><?php endif; ?></th>
			<?php endforeach ?>
			<th class="last grid-remove<?php if (empty($rows)) echo ' hidden'?>"></th>
		</tr>
		<?php
		$last = end($rows);
		reset($rows);
		foreach ($rows as $row):
			$class = '';
			if ($row == $last)
			{
				$class = ' class="last"';
			}
		?>
		<tr<?=$class?>>
			<td class="reorder-col"><span class="ico reorder"></span></td>
			<?php foreach ($columns as $column): ?>
			<td	data-fieldtype="<?=$column['col_type']?>"
				data-column-id="<?=$column['col_id']?>"
				data-row-id="<?=$row['row_id']?>">
				<?=$row['col_id_'.$column['col_id']]?>
			</td>
			<?php endforeach ?>
			<td>
				<ul class="toolbar">
					<li class="remove"><a href="#" title="<?=lang('remove_row')?>"></a></li>
				</ul>
			</td>
		</tr>
		<?php endforeach ?>
		<tr class="grid-blank-row hidden">
			<td class="reorder-col"><span class="ico reorder"></span></td>
			<?php foreach ($columns as $column): ?>
			<td	data-fieldtype="<?=$column['col_type']?>"
				data-column-id="<?=$column['col_id']?>">
				<?=$blank_row['col_id_'.$column['col_id']]?>
			</td>
			<?php endforeach ?>
			<td>
				<ul class="toolbar">
					<li class="remove"><a href="#" title="<?=lang('remove_row')?>"></a></li>
				</ul>
			</td>
		</tr>
		<tr class="no-results<?php if ( ! empty($rows)) echo ' hidden'?>">
			<td class="solo" colspan="<?=count($columns)?>"><?=lang('no_rows_created')?> <a class="btn" href=""><?=lang('add_new_row')?></a></td>
		</tr>
	</table>
</div>
<ul class="toolbar<?php if (empty($rows)) echo ' hidden'?>">
	<li class="add"><a href="#" title="<?=lang('add_new_row')?>"></a></li>
</ul>