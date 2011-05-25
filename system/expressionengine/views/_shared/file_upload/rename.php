<?php $this->load->view('_shared/file_upload/iframe_header'); ?>

<div class="upload_rename">
	<h2><?= lang('upload_rename') ?></h2>
	<p><?= lang('upload_rename_message') ?></p>
	<?= form_open('C=content_files_modal'.AMP.'M=update_file', '', array('file_id' => $file['file_id'], 'file_json' => $file_json, 'file_ext' => $file_ext, 'directory_id' => $file['upload_location_id'])) ?>
	<p><input type="text" name="new_file_name" value="<?= $orig_name ?>" class="text" /><span class="extension">.<?= $file_ext ?></span></p>
	<input type="submit" name="submit" value="Submit" />
	<?= form_close() ?>
</div> <!-- .upload_rename -->

<script>
	var file = <?= $file_json ?>;
	parent.$.ee_fileuploader.file_exists(file);
</script>

<?php $this->load->view('_shared/file_upload/iframe_footer') ?>