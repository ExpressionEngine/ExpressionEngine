<?php extend_template('default-nav'); ?>

<?=form_open(cp_url('utilities/translate/' . $language), 'class="tbl-ctrls"')?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="filter_by_phrase" value="<?=$filter_by_phrase_value?>">
		<input class="btn submit" type="submit" value="<?=lang('search_files_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?php $this->view('_shared/form_messages')?>
	<table cellspacing="0">
		<tr>
			<th class="first highlight"><?=lang('file_name')?> <a href="<?=$file_name_sort_url?>" class="ico sort <?=$file_name_direction?> right"></a></th>
			<th><?=lang('manage')?></th>
			<th class="last check-ctrl"><input type="checkbox" title="<?=strtolower(lang('select_all'))?>"></th>
		</tr>

	<?php foreach($files as $i => $file): ?>
	<?php if ($i == count($files) - 1): ?>
		<tr class="last">
	<?php elseif ($i % 2): ?>
		<tr class="alt">
	<?php else: ?>
		<tr>
	<?php endif; ?>
			<td<?php if ($i == count($files) - 1): ?> class="first"<?php endif; ?>><?=$file['filename']?></td>
			<td>
				<ul class="toolbar">
					<li class="edit"><a href="<?=cp_url('utilities/translate/' . $language . '/edit/' . $file['name'])?>" title="<?=strtolower(lang('edit'))?>"></a></li>
				</ul>
			</td>
			<td<?php if ($i == count($files) - 1): ?> class="last"<?php endif; ?>><input type="checkbox" name="selection[]" value="<?=$file['name']?>"></td>
		</tr>
	<?php endforeach; ?>

	</table>
	<?php $this->view('_shared/pagination'); ?>
	<fieldset class="tbl-bulk-act">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="export"><?=lang('export_download')?></option>
		</select>
		<input class="btn submit" type="submit" value="<?=lang('submit')?>">
	</fieldset>
<?=form_close()?>