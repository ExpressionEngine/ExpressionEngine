<?php $this->load->view('_shared/file/iframe_header'); ?>

<h3 class="closed"><a href="#"><?=lang('file_upload')?></a></h3>

<iframe id='target_upload' name='target_upload' src='' style='width:200px;height:50px;border:1;display:none;'></iframe>
<?=form_open_multipart('C=content_files'.AMP.'M=upload_file', array('id'=>'upload_form'))?>

<p>
<?php if (count($upload_directories) > 1):?>
	<?=form_label(lang('upload_dir_choose'), 'upload_dir')?>
	<?=form_dropdown('upload_dir', $upload_directories, $selected_directory_id, 'id="upload_dir"')?>
<?php else:?>
	<input type="hidden" name="upload_dir" value="<?=key($upload_directories)?>" id="upload_dir" />
<?php endif;?>
</p>

<p>
	<?=form_label(lang('upload_file'), 'upload_file', array('class' => 'visualEscapism'))?>
	<?=form_upload(array('id'=>'upload_file','name'=>'userfile','size'=>15,'class'=>'field'))?>
</p>

<p class="custom_field_add"><button type="submit" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/upload_item.png" width="12" height="14" alt="<?=lang('upload')?>" />&nbsp;&nbsp;<?=lang('upload')?></button></p>

<p id="progress"><img src="<?=$cp_theme_url?>images/indicator.gif" alt="<?=lang('loading')?>..." /><br /><?=lang('loading')?>...</p>

<?=form_close()?>
	
<?php $this->load->view('_shared/file/iframe_footer') ?>