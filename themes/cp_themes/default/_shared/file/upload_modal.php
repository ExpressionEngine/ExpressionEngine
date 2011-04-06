<?php
/**
 * This view is the outer part of the upload modal: the header and buttons
 */
?>

<div id="file_uploader" class="pageContents">
	<iframe src="<?= $base_url ?>"></iframe>
	<div class="button_bar ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
		<input type="submit" class="submit" name="choose_file" value="Choose File" id="choose_file" />
	</div>
</div>

<?php
/* End of file upload_modal.php */
/* Location: ./themes/cp_themes/default/_shared/file/upload_modal.php */
