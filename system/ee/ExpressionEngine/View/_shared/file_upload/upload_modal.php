<div id="file_uploader" class="pageContents">
	<iframe name="upload_iframe" id="upload_iframe" frameBorder="0" class="group"></iframe>
	<div class="button_bar ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
		<a href="#" class="edit_modal filebrowser cancel" id="cancel_changes"><?= lang('cancel_changes') ?></a>
		<img src="<?=PATH_CP_GBL_IMG?>/indicator_upload.gif" alt="<?=lang('loading')?>..." class="before_upload visualEscapism loading" />
		<input type="submit" class="before_upload disabled-btn" name="upload_file" value="<?= lang('upload_file') ?>" id="upload_file" />
		<input type="submit" class="file_exists submit" name="rename_file" value="<?= lang('rename_file') ?>" id="rename_file" />
		<a href="#" class="after_upload filemanager cancel" id="browse_files"><?= lang('browse_files') ?></a>
		<a href="#" class="after_upload filemanager submit" id="edit_file"><?= lang('edit_file') ?></a>
		<a href="#" class="after_upload filemanager submit" id="edit_image"><?= lang('edit_image') ?></a>
		<input type="submit" class="after_upload filebrowser submit" name="edit_file_modal" value="<?= lang('edit_file') ?>" id="edit_file_modal" />
		<input type="submit" class="edit_modal filebrowser submit" name="save_file" value="<?= lang('save_file') ?>" id="save_file" />
		<input type="submit" class="after_upload edit_modal filebrowser submit" name="choose_file" value="<?= lang('use_file') ?>" id="choose_file" />
	</div>
</div>
<script>
// This is a super clean and not at all silly fix for bug #19196.
function _EE_uploader_attached()
{
	$.ee_fileuploader.setSource('#upload_iframe', '<?=str_replace(AMP, "&", $base_url)?>');
}
</script>


<?php

