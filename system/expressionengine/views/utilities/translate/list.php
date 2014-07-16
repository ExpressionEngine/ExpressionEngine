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
			<th class="first highlight"><?=lang('file_name')?> <a href="#" class="ico sort asc right"></a></th>
			<th><?=lang('manage')?></th>
			<th class="last check-ctrl"><input type="checkbox" title="<?=strtolower(lang('select_all'))?>"></th>
		</tr>
		<tr>
			<td>addons_lang.php</td>
			<td>
				<ul class="toolbar">
					<li class="edit"><a href="<?=cp_url('utilities/translate/' . $language . '/edit')?>" title="<?=strtolower(lang('edit'))?>"></a></li>
				</ul>
			</td>
			<td><input type="checkbox"></td>
		</tr>
		<tr class="alt">
			<td>admin_content_lang.php</td>
			<td>
				<ul class="toolbar">
					<li class="edit"><a href="<?=cp_url('utilities/translate/' . $language . '/edit')?>" title="<?=strtolower(lang('edit'))?>"></a></li>
				</ul>
			</td>
			<td><input type="checkbox"></td>
		</tr>

		<tr class="last">
			<td class="first">xmlrpc_lang.php</td>
			<td>
				<ul class="toolbar">
					<li class="edit"><a href="<?=cp_url('utilities/translate/' . $language . '/edit')?>" title="<?=strtolower(lang('edit'))?>"></a></li>
				</ul>
			</td>
			<td class="last"><input type="checkbox"></td>
		</tr>
	</table>
	<?php $this->view('_shared/pagination'); ?>
	<fieldset class="tbl-bulk-act">
		<select>
			<option>-- <?=lang('with_selected')?> --</option>
			<option><?=lang('export_download')?></option>
		</select>
		<input class="btn submit" type="submit" value="<?=lang('submit')?>">
	</fieldset>
<?=form_close()?>