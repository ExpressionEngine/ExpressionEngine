<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open(ee('CP/URL', 'utilities/stats/sync'))?>
		<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
		<?=ee('Alert')->getAllInlines()?>
		<table cellspacing="0">
			<thead>
				<tr>
					<th<?php if($highlight == 'source'): ?> class="highlight"<?php endif; ?>><?=lang('source')?> <a href="<?=$source_sort_url?>" class="sort <?=$source_direction?>"></a></th>
					<th<?php if($highlight == 'record_count'): ?> class="highlight"<?php endif; ?>><?=lang('record_count')?> <a href="<?=$record_count_sort_url?>" class="sort <?=$record_count_direction?>"></a></th>
					<th><?=lang('manage')?></th>
					<th class="check-ctrl"><input type="checkbox" title="<?=strtolower(lang('select_all'))?>"></th>
				</tr>
			</thead>

			<tbody>
				<?php foreach($sources as $source => $count): ?>
					<tr>
						<td><?=lang($source)?></td>
						<td><?=$count?></td>
						<td>
							<ul class="toolbar">
								<li class="sync"><a href="<?=ee('CP/URL', 'utilities/stats/sync/' . $source)?>" title="<?=strtolower(lang('sync'))?>"></a></li>
							</ul>
						</td>
						<td><input type="checkbox" name="selection[]" value="<?=$source?>"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>

		</table>

		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="sync"><?=lang('sync')?></option>
			</select>
			<input class="btn submit" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	<?=form_close()?>
</div>
