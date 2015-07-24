<?php $this->embed('_shared/file_upload/iframe_header'); ?>

<div class="upload_rename">
	<h2><?= lang('upload_rename') ?>: <?= $original_name ?></h2>
	
	<p><?= lang('upload_rename_message') ?></p>
	<?= form_open('C=content_files_modal'.AMP.'M=update_file', '', $hidden) ?>
		<p><input type="text" name="new_file_name" value="<?= $original_name ?>" class="text" /><span class="extension">.<?= $file_extension ?></span></p>
	<?= form_close() ?>
</div> <!-- .upload_rename -->

<script>
	var file = <?= $file_json ?>;
	parent.$.ee_fileuploader.file_exists(file);
</script>

<?php $this->embed('_shared/file_upload/iframe_footer') ?>