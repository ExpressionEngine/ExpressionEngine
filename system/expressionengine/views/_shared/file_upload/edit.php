<?php $this->load->view('_shared/file_upload/iframe_header'); ?>

<div class="upload_edit">
	<h2><?= lang('upload_edit') ?></h2>
	
	<div class="image">
		<img src="<?= $file['thumb'] ?>" alt="<?= $file['file_name'] ?>" />
	</div> <!-- .image -->
	<div class="edit_controls">
		<?=form_open('C=content_files_modal'.AMP.'M=edit_image', array('id' => 'image_resize_form'), $file_data)?>
			<?= $file_json_input ?>
			<?= form_hidden('action', 'resize')?>
			<h3 class="accordion"><?=lang('resize')?></h3>
			<div id="resize_fieldset">
				<ul>
					<li>
						<?=lang('resize_width', 'resize_width')?>
						<?=form_input('resize_width', $file['file_width'], 'id="resize_width"')?>
					</li>
					<li>
						<?=lang('resize_height', 'resize_height')?>
						<?=form_input('resize_height', $file['file_height'], 'id="resize_height"')?>
					</li>
				</ul>
				<p class="submit_button">
					<?=form_submit('save_image', lang('save_image'), 'id="submit_resize" class="submit"')?><br />
					<?=anchor('#', lang('cancel_changes'), 'id="cancel_resize" style="display: none"')?>
				</p>
			</div>
			<div class="clear_left"></div>
		<?=form_close()?>
		<?=form_open('C=content_files_modal'.AMP.'M=edit_image', array('id' => 'image_rotate_form'), $file_data)?>
			<?= $file_json_input ?>
			<?= form_hidden('action', 'rotate')?>
			<h3 class="accordion"><?=lang('rotate')?></h3>
			<div id="rotate_fieldset">
				<ul>
					<li class="rotate_90">
						<label>
							<?php // Rotate 90 degrees right is 270 because 
								  // the image lib rotates counter-clockwise ?>
							<?=form_radio('rotate', '270', TRUE)?>
							<?=lang('rotate_90r')?>
						</label>
					</li>
					<li class="rotate_270">
						<label>
							<?=form_radio('rotate', '90', TRUE)?>
							<?=lang('rotate_90l')?>
						</label>
					</li>
					<li class="rotate_vrt">
						<label>
							<?=form_radio('rotate', 'vrt', TRUE)?>
							<?=lang('rotate_flip_vert')?>
						</label>
					</li>
					<li class="rotate_hor">
						<label>
							<?=form_radio('rotate', 'hor', TRUE)?>
							<?=lang('rotate_flip_hor')?>
						</label>
					</li>
				</ul>
				<p class="submit_button">
					<?=form_submit('save_image', lang('save_image'), 'class="submit"')?><br />
				</p>
			</div>
		<?=form_close()?>
	</div> <!-- .edit_controls -->
</div> <!-- .upload_edit -->

<script>
	var file = <?= $file_json ?>;
	parent.$.ee_fileuploader.update_file(file);
</script>

<?php $this->load->view('_shared/file_upload/iframe_footer') ?>