<?php use EllisLab\ExpressionEngine\Library\CP\Table;
if ($wrap): ?>
	<div class="tbl-wrap">
<?php endif ?>

<?php if (empty($columns) && empty($data)): ?>
	<table cellspacing="0" class="empty no-results">
		<tr>
			<td>
				<?=lang($no_results['text'])?>
				<?php if ( ! empty($no_results['action_text'])): ?>
					<a class="btn action" href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>>
				<?php endif ?>
			</td>
		</tr>
	</table>
<?php else: ?>
	<table cellspacing="0">
		<tr>
			<?php foreach ($columns as $label => $settings): ?>
				<?php if ($settings['type'] == Table::COL_CHECKBOX): ?>
					<th class="check-ctrl">
						<?php if ( ! empty($data)): // Hide checkbox if no data ?>
							<input type="checkbox" title="select all">
						<?php endif ?>
					</th>
				<?php else: ?>
					<?php
					$table_class = '';
					if ($settings['type'] == Table::COL_ID)
					{
						$table_class .= ' id-col';
					}
					if ($settings['sort'] && $sort_col == $label)
					{
						$table_class .= ' highlight';
					} ?>
					<th<?php if ( ! empty($table_class)): ?> class="<?=trim($table_class)?>"<?php endif ?>>
						<?=($lang_cols) ? lang($label) : $label ?>
						<?php if ($settings['sort']): ?>
							<?php
							$arrow_dir = ($sort_col == $label) ? $sort_dir : 'desc';
							$link_dir = ($arrow_dir == 'asc') ? 'desc' : 'asc';
							$base_url->setQueryStringVariable('sort_col', $label);
							$base_url->setQueryStringVariable('sort_dir', $link_dir);
							?>
							<a href="<?=$base_url?>" class="ico sort <?=$arrow_dir?> right"></a>
						<?php endif ?>
					</th>
				<?php endif ?>
			<?php endforeach ?>
		</tr>
		<?php if (empty($data)): ?>
			<tr class="no-results">
				<td class="solo" colspan="<?=count($columns)?>">
					<?=lang($no_results['text'])?>
					<?php if ( ! empty($no_results['action_text'])): ?>
						<a class="btn action" href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>
					<?php endif ?>
				</td>
			</tr>
		<?php endif ?>
		<?php foreach ($data as $row): ?>
			<tr>
				<?php foreach ($row as $column): ?>
					<?php if ($column['encode'] == TRUE): ?>
						<td><?=htmlspecialchars($column['content'])?></td>
					<?php elseif ($column['type'] == Table::COL_TOOLBAR): ?>
						<td>
							<ul class="toolbar">
								<?php foreach ($column['toolbar_items'] as $type => $attributes):
									$attr = '';
									foreach ($attributes as $key => $val)
									{
										$attr .= ' ' . $key . '="' . $val . '"';
									} ?>
									<li class="<?=$type?>"><a <?=$attr?>></a></li>
								<?php endforeach ?>
							</ul>
						</td>
					<?php elseif ($column['type'] == Table::COL_CHECKBOX): ?>
						<td>
							<input
								name="<?=$column['name']?>"
								value="<?=$column['value']?>"
								<?php if (isset($column['data'])):?>
									<?php foreach ($column['data'] as $key => $value): ?>
										data-<?=$key?>="<?=$value?>"
									<?php endforeach; ?>
								<?php endif; ?>
								type="checkbox"
							>
						</td>
					<?php elseif ($column['type'] == Table::COL_STATUS): ?>
						<td><span class="st-<?=$column['content']?>"><?=$column['content']?></span></td>
					<?php else: ?>
						<td><?=$column['content']?></td>
					<?php endif ?>
				<?php endforeach ?>
			</tr>
		<?php endforeach ?>
	</table>
<?php endif ?>

<?php if ($wrap): ?>
	</div>
<?php endif ?>