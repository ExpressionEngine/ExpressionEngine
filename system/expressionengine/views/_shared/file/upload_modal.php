<div id="file_uploader" class="pageContents">
	<iframe name="upload_iframe" src="<?= $base_url ?>"></iframe>
	<div class="button_bar ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix upload_step_1">
		<input type="submit" class="before_upload submit" name="upload_file" value="<?= lang('upload_file') ?>" id="upload_file" />
		<a href="#" class="after_upload filemanager cancel" id="browse_files"><?= lang('browse_files') ?></a>
		<a href="#" class="after_upload filemanager submit" id="edit_file"><?= lang('edit_file') ?></a>
		<input type="submit" class="after_upload filebrowser submit" name="choose_file" value="<?= lang('use_file') ?>" id="choose_file" />
	</div>
</div>


<?php
/* End of file upload_modal.php */
/* Location: ./themes/cp_themes/default/_shared/file/upload_modal.php */
