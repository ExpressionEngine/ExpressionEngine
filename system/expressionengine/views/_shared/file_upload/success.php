<?php $this->load->view('_shared/file_upload/iframe_header'); ?>

<div class="upload_success">
	<h2><?= lang('upload_success') ?></h2>
	
	<div class="image">
		<img src="<?= $file['thumb'] ?>" alt="<?= $file['file_name'] ?>" />
		<?php if ($file['is_image']): ?>
			<?= form_open('C=content_files_modal'.AMP.'M=edit_image', array('id' => "resize_rotate"), array('file_json' => $file_json)) ?>
			<?= form_close() ?>
		<?php endif ?>
	</div> <!-- .image -->
	<table>
		<tr class="odd">
			<th>File Name</th>
			<td><?= $file['file_name'] ?></td>
		</tr>
		<tr>
			<th>Upload Directory</th>
			<td><?= $file['upload_directory_prefs']['name'] ?></td>
		</tr>
		<tr class="odd">
			<th>Kind</th>
			<td><?= $file['mime_type'] ?></td>
		</tr>
		<tr>
			<th>File Size</th>
			<td><?= $file['file_size'] ?></td>
		</tr>
		<?php if ($file['is_image']): ?>
			<tr class="odd">
				<th>Height</th>
				<td><?= $file['file_height'] ?>px</td>
			</tr>
			<tr>
				<th>Width</th>
				<td><?= $file['file_width'] ?>px</td>
			</tr>
		<?php endif ?>
	</table>	
</div> <!-- .success -->

<script>
	var file = <?= $file_json ?>;
	parent.$.ee_fileuploader.after_upload(file);
</script>

<?php $this->load->view('_shared/file_upload/iframe_footer') ?>