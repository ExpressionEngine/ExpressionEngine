<div id="file_browser" class="pageContents" style="padding: 0 10px">
	<div id="filterMenu">
		<?php if ( ! empty($filemanager_directories)):?>
			<?=form_open('', array('id' => 'dir_choice_form'))?>
				<span class="dir_choice_container">
					<?=lang('upload_directory', 'dir_choice').NBS?>
					<?=form_dropdown('dir_choice', $filemanager_directories, key($filemanager_directories), 'id="dir_choice"').NBS?>
				</span>
				<input type="text" name="keywords" value="" id="keywords" placeholder="<?= lang('keywords') ?>" />
			<?=form_close()?>
			<div class="tableSubmit" id="upload_form">
				<input type="submit" class="submit" value="<?=lang('upload_file')?>">
			</div> <!-- .tableSubmit -->
		<?php endif; ?>
		<div class="clear_left"></div>
	</div>
	
	<div id="file_browser_body" class="">
	
		<?=$table_html?>
		
		<script type="text/x-jquery-tmpl" id="thumbTmpl">
			<a title="${name}" href="#" onclick="$.ee_filebrowser.placeImage('${file_id}'); return false;" class="file_browser_thumbnail">
				<img src="${thumb}?r=${modified_date}" class="${thumb_class}" data-dimensions="${file_hw_original}" />
				<p>${short_name}</p>
			</a>
		</script>
	</div>
	
	<div id="file_browser_footer">
		<p><?=sprintf(lang('pagination_filter_text'), $view_filters).NBS?></p>
		<?=$pagination_html?>
	</div>
</div>

<?php
/* End of file filebrowser.php */
/* Location: ./themes/cp_themes/default/_shared/file_upload/browser.php */
