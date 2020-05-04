<?php $this->embed('_shared/file_upload/iframe_header'); ?>

<?=form_open_multipart('C=content_files_modal'.AMP.'M=upload_file', array('id'=>'upload_form'), $hidden_vars)?>
	<?php if (isset($error)): ?>
		<div class="notice">
			<?=$error?>
		</div>
		<script>
			parent.$.ee_fileuploader.reset_upload();
		</script>
	<?php endif ?>
	<p class="dir_choice_container">
		<?php if (count($upload_directories) > 1):?>
			<?=form_label(lang('upload_dir_choose'), 'upload_dir')?>
			<?=form_dropdown('upload_dir', $upload_directories, $selected_directory_id, 'id="upload_dir"')?>
		<?php else:?>
			<?=form_label(sprintf(lang('upload_to'), current($upload_directories)), 'file_upload')?>
			<input type="hidden" name="upload_dir" value="<?=key($upload_directories)?>" id="upload_dir" />
		<?php endif;?>
	</p>
	<p>
		<?=form_label(lang('upload_file'), 'file_upload', array('class' => 'visualEscapism'))?>
		<?=form_upload(array('id'=>'file_upload','name'=>'userfile','size'=>15,'class'=>'field'))?>
	</p>
<?=form_close()?>

<script>
	$('#file_upload').change(function() {
		if ($(this).val() != '') {
			parent.$.ee_fileuploader.enable_upload();
		};
	});
</script>
	
<?php $this->embed('_shared/file_upload/iframe_footer') ?>