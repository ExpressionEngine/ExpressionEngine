<div id="fileChooser" class="pageContents" style="padding: 0 10px">
	<div id="filterMenu">
		<?php if ( ! empty($filemanager_directories)):?>
			<?=form_open('', array('id' => 'dir_choice_form'))?>
				<?=form_label('Upload Directory:', 'dir_choice').NBS?>
				<?=form_dropdown('dir_choice', $filemanager_directories, key($filemanager_directories), 'id="dir_choice"').NBS?>
			<?=form_close()?>
			<div class="tableSubmit" id="upload_form">
				<input type="submit" class="submit" value="<?=lang('upload_file')?>">
			</div> <!-- .tableSubmit -->
		<?php endif; ?>
		<div class="clear_left"></div>
	</div>
	
	<div id="file_chooser_body" class="">
	
		<table class="mainTable padTable" id="tableView" border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th><?=lang('name')?></th>
					<th><?=lang('size')?></th>
					<th><?=lang('kind')?></th>
					<th><?=lang('date')?></th>
				</tr>
			</thead>
			<tbody>
				<tr id="noFilesRowTmpl">
					<td colspan="4"><?=lang('no_uploaded_files')?></td>
				</tr>
				<tr id="rowTmpl">
					<td><a href="#" onclick="$.ee_filebrowser.placeImage('${file_id}'); return false;">${file_name}</a></td>
					<td>${file_size}</td>
					<td>${mime_type}</td>
					<td>${date}</td>
				</tr>
			</tbody>
		</table>
		
		<script type="text/x-jquery-tmpl" id="thumbTmpl">
			<a title="${file_name}" href="#" onclick="$.ee_filebrowser.placeImage('${file_id}'); return false;" class="file_chooser_thumbnail">
				<img src="${thumb}" class="${thumb_class}" data-dimensions="${file_hw_original}" />
				<p>${short_name}</p>
			</a>
		</script>
	</div>
	
	<div id="file_chooser_footer">
		<div id="paginationTmpl">
			<p id="pagination_meta">
				<?=sprintf(lang('pagination_filter_text'), $view_filters).NBS;?>
				<br /><?=sprintf(lang('pagination_count_text'), '${pages_from}', '${pages_to}', '${pages_total}');?>
			</p>
			<p id="paginationLinks">
				{{if pagination_needed}}
					{{html previous}}
					{{html dropdown}}
					{{html next}}
				{{/if}}
			</p>
			
		</div>
	</div>
</div>

<?php
/* End of file filebrowser.php */
/* Location: ./themes/cp_themes/default/_shared/file_upload/browser.php */
