<div id="file_uploader" class="pageContents">
	<iframe name="upload_iframe" src="<?= $base_url ?>" frameBorder="0"></iframe>
	<div class="button_bar ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix upload_step_1">
		<img src="<?=PATH_CP_GBL_IMG?>/indicator_upload.gif" alt="<?=lang('loading')?>..." class="before_upload visualEscapism loading" />
		<input type="submit" class="before_upload disabled-btn" name="upload_file" value="<?= lang('upload_file') ?>" id="upload_file" />
		<input type="submit" class="file_exists submit" name="rename_file" value="<?= lang('rename_file') ?>" id="rename_file" />
		<a href="#" class="after_upload filemanager cancel" id="browse_files"><?= lang('browse_files') ?></a>
		<a href="#" class="after_upload filemanager submit" id="edit_file"><?= lang('edit_file') ?></a>
		<input type="submit" class="after_upload filebrowser submit" name="edit_file_modal" value="<?= lang('edit_file') ?>" id="edit_file_modal" />
		<input type="submit" class="after_upload filebrowser submit" name="choose_file" value="<?= lang('use_file') ?>" id="choose_file" />
	</div>
</div>


<?php
/* End of file upload_modal.php */
/* Location: ./themes/cp_themes/default/_shared/file_upload/upload_modal.php */
