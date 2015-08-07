<?php $this->embed('_shared/file_upload/iframe_header'); ?>

<div class="upload_success">
	<h2><?= lang('upload_success') ?></h2>
	<div class="image">
		<img src="<?= $file['thumb'] ?>" alt="<?= $file['file_name'] ?>" />
	</div> <!-- .image -->
	<table>
		<tr class="odd">
			<th><?=lang('file_name')?></th>
			<td><?= $file['file_name'] ?></td>
		</tr>
		<tr>
			<th><?=lang('dir_name')?></th>
			<td><?= $file['upload_directory_prefs']['name'] ?></td>
		</tr>
		<tr class="odd">
			<th><?=lang('kind')?></th>
			<td><?= $file['mime_type'] ?></td>
		</tr>
		<tr>
			<th><?=lang('file_size')?></th>
			<td><?= $file['file_size'] ?></td>
		</tr>
		<?php if ($file['is_image']): ?>
			<tr class="odd">
				<th><?=lang('height')?></th>
				<td><?= $file['dimensions'][0] ?>px</td>
			</tr>
			<tr>
				<th><?=lang('width')?></th>
				<td><?= $file['dimensions'][1] ?>px</td>
			</tr>
		<?php endif ?>
	</table>	
</div> <!-- .success -->

<?= form_open('C=content_files_modal'.AMP.'M=edit_file', array('id' => "edit_file"), array('file_id' => $file_id)) ?>
<?= form_close() ?>

<script>
	var file = <?= $file_json ?>;
	parent.$.ee_fileuploader.after_upload(file);
</script>

<?php $this->embed('_shared/file_upload/iframe_footer') ?>