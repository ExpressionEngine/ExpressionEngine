<style type="text/css">
/* Hack for capybara-webkit, leave in place for now */
a.sort {
	display: inline-block;
	width: 1px;
}
</style>

<?php use EllisLab\ExpressionEngine\Library\CP\Table;
if ($wrap): ?>
	<div class="tbl-wrap<?php if ($grid_input): ?> pb<?php endif?>">
<?php endif ?>

<?php if (empty($columns) && empty($data)): ?>
	<table cellspacing="0" class="empty no-results">
		<tr>
			<td>
				<?=lang($no_results['text'])?>
				<?php if ( ! empty($no_results['action_text'])): ?>
					<a class="btn action" <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>>
				<?php endif ?>
			</td>
		</tr>
	</table>
<?php else: ?>
	<table cellspacing="0"<?php if ($grid_input): $class .= ' grid-input-form'; ?> id="<?=$grid_field_name?>"<?php endif?> <?php if ($class): ?>class="<?=$class?>"<?php endif ?> <?php foreach ($table_attrs as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>
		<thead>
			<tr>
				<?php
				// Don't do reordering logic if the table is empty
				$reorder = $reorder && ! empty($data);
				$colspan = ($reorder_header || $reorder) ? count($columns) + 1 : count($columns);
				if ($grid_input)
				{
					$colspan++;
				}

				if ($reorder_header): ?>
					<th class="reorder-col"><span class="ico reorder"></span></th>
				<?php elseif ($reorder): ?>
					<th class="first reorder-col"></th>
				<?php endif ?>
				<?php foreach ($columns as $settings):
					$attrs = (isset($settings['attrs'])) ? $settings['attrs'] : array();
					$label = $settings['label']; ?>
					<?php if ($settings['type'] == Table::COL_CHECKBOX): ?>
						<th class="check-ctrl">
							<?php if ( ! empty($data)): // Hide checkbox if no data ?>
								<input type="checkbox" title="select all">
							<?php endif ?>
						</th>
					<?php else: ?>
						<?php
						$header_class = '';
						if ($settings['type'] == Table::COL_ID)
						{
							$header_class .= ' id-col';
						}
						if ($sortable && $settings['sort'] && $sort_col == $label)
						{
							$header_class .= ' highlight';
						}
						if (isset($settings['class']))
						{
							$header_class .= ' '.$settings['class'];
						}
						?>
						<th<?php if ( ! empty($header_class)): ?> class="<?=trim($header_class)?>"<?php endif ?><?php foreach ($attrs as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>
							<?php if (isset($settings['required']) && $settings['required']): ?><span class="required"><?php endif; ?>
							<?=($lang_cols) ? lang($label) : $label ?>
							<?php if (isset($settings['required']) && $settings['required']): ?></span><?php endif; ?>
							<?php if (isset($settings['desc']) && ! empty($settings['desc'])): ?>
								<span class="grid-instruct"><?=lang($settings['desc'])?></span>
							<?php endif ?>
							<?php if ($sortable && $settings['sort'] && $base_url != NULL): ?>
								<?php
								$url = clone $base_url;
								$arrow_dir = ($sort_col == $label) ? $sort_dir : 'desc';
								$link_dir = ($arrow_dir == 'asc') ? 'desc' : 'asc';
								$url->setQueryStringVariable($sort_col_qs_var, $label);
								$url->setQueryStringVariable($sort_dir_qs_var, $link_dir);
								?>
								<a href="<?=$url?>" class="sort <?=$arrow_dir?>"></a>
							<?php endif ?>
						</th>
					<?php endif ?>
				<?php endforeach ?>
				<?php if ($grid_input && ! empty($data)): ?>
					<th class="last grid-remove"></th>
				<?php endif ?>
			</tr>
		</thead>
		<tbody>
			<?php
			// Output this if Grid input so we can dynamically show it via JS
			if (empty($data) OR $grid_input): ?>
				<tr class="no-results<?php if ($grid_input): ?> hidden<?php endif?><?php if ( ! empty($action_buttons) || ! empty($action_content)): ?> last<?php endif?>">
					<td class="solo" colspan="<?=$colspan?>">
						<?=lang($no_results['text'])?>
						<?php if ( ! empty($no_results['action_text'])): ?>
							<a class="btn action" <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>
						<?php endif ?>
					</td>
				</tr>
			<?php endif ?>
			<?php $i = 1;
			foreach ($data as $heading => $rows): ?>
				<?php if ( ! $subheadings)
				{
					$rows = array($rows);
				}
				if ($subheadings && ! empty($heading)): ?>
					<tr class="sub-heading"><td colspan="<?=$colspan?>"><?=lang($heading)?></td></tr>
				<?php endif ?>
				<?php
				foreach ($rows as $row):
					// The last row preceding an action row should have a class of 'last'
					if (( ! empty($action_buttons) || ! empty($action_content)) && $i == min($total_rows, $limit))
					{
						if (isset($row['attrs']['class']))
						{
							$row['attrs']['class'] .= ' last';
						}
						else
						{
							$row['attrs']['class'] = ' last';
						}
					}
					$i++;
					?>
					<tr<?php foreach ($row['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>
						<?php if ($reorder): ?>
							<td class="reorder-col"><span class="ico reorder"></span></td>
						<?php endif ?>
						<?php foreach ($row['columns'] as $column): ?>
							<?php if ($column['encode'] == TRUE && $column['type'] != Table::COL_STATUS): ?>
								<?php if (isset($column['href'])): ?>
								<td><a href="<?=$column['href']?>"><?=htmlentities($column['content'], ENT_QUOTES, 'UTF-8')?></a></td>
								<?php else: ?>
								<td><?=htmlentities($column['content'], ENT_QUOTES, 'UTF-8')?></td>
								<?php endif; ?>
							<?php elseif ($column['type'] == Table::COL_TOOLBAR): ?>
								<td>
									<div class="toolbar-wrap">
										<?=ee()->load->view('_shared/toolbar', $column, TRUE)?>
									</div>
								</td>
							<?php elseif ($column['type'] == Table::COL_CHECKBOX): ?>
								<td>
									<input
										name="<?=form_prep($column['name'])?>"
										value="<?=form_prep($column['value'])?>"
										<?php if (isset($column['data'])):?>
											<?php foreach ($column['data'] as $key => $value): ?>
												data-<?=$key?>="<?=form_prep($value)?>"
											<?php endforeach; ?>
										<?php endif; ?>
										<?php if (isset($column['disabled']) && $column['disabled'] !== FALSE):?>
											disabled="disabled"
										<?php endif; ?>
										type="checkbox"
									>
								</td>
							<?php elseif ($column['type'] == Table::COL_STATUS): ?>
								<?php $class = isset($column['class']) ? $column['class'] : $column['content']; ?>
								<?php
									$style = 'style="';
									if ($class != 'open' && $class != 'closed')
									{
										if (isset($column['background-color']) && $column['background-color'])
										{
											$style .= 'background-color: #'.$column['background-color'].';';
											$style .= 'border-color: #'.$column['background-color'].';';
										}

										if (isset($column['color']) && $column['color'])
										{
											$style .= 'color: #'.$column['color'].';';
										}
									}

									$style .= '"';
								?>
								<td><span class="status-tag st-<?=strtolower($class)?>" <?=$style?>><?=$column['content']?></span></td>
							<?php elseif (isset($column['html'])): ?>
								<td<?php if (isset($column['error']) && ! empty($column['error'])): ?> class="invalid"<?php endif ?> <?php if (isset($column['attrs'])): foreach ($column['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; endif; ?>>
									<?=$column['html']?>
									<?php if (isset($column['error']) && ! empty($column['error'])): ?>
										<em class="ee-form-error-message"><?=$column['error']?></em>
									<?php endif ?>
								</td>
							<?php else: ?>
								<td><?=$column['content']?></td>
							<?php endif ?>
						<?php endforeach ?>
						<?php if ($grid_input): ?>
							<td>
								<ul class="toolbar">
									<li class="remove"><a href="#" title="remove row"></a></li>
								</ul>
							</td>
						<?php endif ?>
					</tr>
				<?php endforeach ?>
			<?php endforeach ?>
			<?php if ( ! empty($action_buttons) || ! empty($action_content)): ?>
				<tr class="tbl-action">
					<td colspan="<?=$colspan?>" class="solo">
						<?php foreach ($action_buttons as $button): ?>
							<a class="<?=$button['class']?>" href="<?=$button['url']?>"><?=$button['text']?></a></td>
						<?php endforeach; ?>
						<?=$action_content?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
<?php endif ?>

<?php if ($wrap): ?>
	</div>
<?php endif ?>

<?php if ($grid_input && ! empty($data)): ?>
	<ul class="toolbar">
		<li class="add"><a href="#" title="<?=lang('add_new_row')?>"></a></li>
	</ul>
<?php endif ?>
