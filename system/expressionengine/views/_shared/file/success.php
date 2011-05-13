<?php $this->load->view('_shared/file/iframe_header'); ?>

<div class="upload_success">
	<h2><?= $success ?></h2>

	<img src="<?= $file['thumb'] ?>" alt="<?= $file['file_name'] ?>" />
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

<?php $this->load->view('_shared/file/iframe_footer') ?>