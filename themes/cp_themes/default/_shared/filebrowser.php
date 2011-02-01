<?php

$dir_size = 4180;

?>

<div id="fileChooser" class="pageContents" style="padding: 0 10px">
	<style type="text/css" media="screen">
		#file_chooser_body {
			display: block;
			overflow: auto;
			height: 355px;
			width: 100%;
			
			border-top: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
		}
	</style>
	<div class="shun"></div>
	<div id="filterMenu">
		
		<?php if ( ! empty($filemanager_directories)):?>
			<?=form_open('', array('id' => 'dir_choice_form'))?>
			
				<?=form_label('Upload Directory:', 'dir_choice').NBS?>
				<?=form_dropdown('dir_choice', $filemanager_directories, key($filemanager_directories), 'id="dir_choice"').NBS?>
				<?php /*
				<small><?=lang('total_dir_size')?> <?=number_format($dir_size/1000, 1);?> <?=lang('file_size_unit')?></small>
				*/ ?>
				
			<?=form_close()?>
			<iframe id='target_upload' name='target_upload' src='about:blank' style='width:200px;height:50px;border:1;display:none;'></iframe>

			<?=form_open_multipart($filemanager_backend_url.'&action=upload', array('target'=>'target_upload','id'=>'upload_form', 'class'=>'tableSubmit'))?>
				<input type="hidden" name="frame_id" value="target_upload" id="frame_id" />

				<?=form_label(lang('upload_file'), 'upload_file', array('class' => 'visualEscapism'))?>
				<?=form_hidden('upload_dir', key($filemanager_directories))?>
				<?=form_upload(array('id'=>'upload_file','name'=>'userfile','size'=>15,'class'=>'field'))?>
				&nbsp;&nbsp;<input type="submit" class="submit" value="<?=lang('upload_file')?>">.
				
			<?=form_close()?>
		<?php endif; ?>
		<div class="clear_left"></div>
	</div>
	
	<div id="file_chooser_body" class="shun">
	
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
					<td><a href="#" onclick="$.ee_filebrowser.placeImage(${directory}, ${img_id}); return false;">${name}</a></td>
					<td>${size}</td>
					<td>${mime}</td>
					<td>${date}</td>
				</tr>
			</tbody>
		</table>
		
		<div id="thumbTmpl">
			<a title="${name}" href="#" onclick="$.ee_filebrowser.placeImage(${directory}, ${img_id}); return false;" style="width: 73px; height: 70px; padding: 2px; float: left; margin: 10px;">
				<img src="${thumb}" data-dimensions="${dimensions}" /><br>
				<p>${short_name}</p>
			</a>
		</div>
	</div>
	
	<div id="file_chooser_footer">
		<div id="viewSelectors" style="float: right;">
			<p>
				<label for="list_view"><input type="radio" name="view_type" value="list" id="list_view" checked> List</label>
				<br>
				<label for="thumb_view"><input type="radio" name="view_type" value="thumb" id="thumb_view"> Thumbnails</label>
			</p>
		</div>
		
		<div id="paginationTmpl">
			{{if pages.length}}
			<p id="paginationCount"><?=sprintf(lang('pagination_count_text'), '${pages_from}', '${pages_to}', '${pages_total}');?></p>
			<p id="paginationLinks">
				{{each pages}}
					{{if $value == pages_current}}
					&nbsp;<strong>${$value}</strong>
					{{else}}
					&nbsp;<a href="#" onclick="$.ee_filebrowser.setPage(${directory}, ${$index}); return false;">${$value}</a>
					{{/if}}
				{{/each}}&nbsp;
			</p>
			{{/if}}
		</div>
	</div>
</div>

<?php
/* End of file filebrowser.php */
/* Location: ./themes/cp_themes/default/_shared/filebrowser.php */