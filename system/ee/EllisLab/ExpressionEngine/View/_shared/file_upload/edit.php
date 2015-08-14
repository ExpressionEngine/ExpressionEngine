<?php $this->embed('_shared/file_upload/iframe_header'); ?>

<div class="upload_edit">
	<?=form_open('C=content_files_modal'.AMP.'M=edit_file', array('id' => 'edit_file_metadata'), $hidden)?>
		<ul class="panel-menu group">
			<?php foreach ($tabs as $index => $tab): ?>
				<li class="<?=($index == 0) ? 'current' : ''?>">
					<a href="#" data-panel="<?=$tab?>"><?=lang($tab)?></a>&nbsp;
				</li>
			<?php endforeach ?>
		</ul>
		<div class="panels group">
			<div id="file_metadata" class="group current">
				<ul>
					<?php foreach ($metadata_fields as $field_name => $field): ?>
						<li>
							<?=lang($field_name, $field_name)?>
							<?=$field?>
							<?=form_error($field_name)?>
						</li>
					<?php endforeach ?>
				</ul>
			</div> <!-- #file_metadata -->
			<?php if ($file['is_image']): ?>
				<div id="image_tools" class="group">
					<div class="image group">
						<img src="<?= $file['thumb'] ?>" alt="<?= $file['file_name'] ?>" />
					</div> <!-- .image -->
					<fieldset id="resize">
						<legend><?= lang('resize') ?> &ldquo;<?= $file['file_name'] ?>&rdquo;</legend>
						<ul class="group">
							<li>
								<?=lang('resize_height', 'resize_height')?>
								<?=form_input(array(
									'name'			=> 'resize_height',
									'value'			=> $file['dimensions'][0],
									'id'			=> 'resize_height',
									'data-default'	=> $file['dimensions'][0]
								))?>
								<?=form_hidden('resize_height_default', $file['dimensions'][0])?>
							</li>
							<li>
								<?=lang('resize_width', 'resize_width')?>
								<?=form_input(array(
									'name'			=> 'resize_width',
									'value'			=> $file['dimensions'][1],
									'id'			=> 'resize_width',
									'data-default'	=> $file['dimensions'][1]
								))?>
								<?=form_hidden('resize_width_default', $file['dimensions'][1])?>
							</li>
						</ul>
					</fieldset>
					<fieldset id="rotate">
						<legend><?= lang('rotate') ?> &ldquo;<?= $file['file_name'] ?>&rdquo;</legend>
						<ul class="group">
							<li class="rotate_90">
								<label>
									<?php // Rotate 90 degrees right is 270 because
										  // the image lib rotates counter-clockwise ?>
									<?=form_radio(array(
										'name'	=> 'rotate',
										'value'	=> 270
									))?>
									<?=lang('rotate_90r')?>
								</label>
							</li>
							<li class="rotate_270">
								<label>
									<?=form_radio(array(
										'name'	=> 'rotate',
										'value'	=> 90
									))?>
									<?=lang('rotate_90l')?>
								</label>
							</li>
						</ul>
						<ul class="group">
							<li class="rotate_vrt">
								<label style="background-image: url(<?=PATH_CP_GBL_IMG?>it-vert-arrow.png)">
									<?=form_radio(array(
										'name'	=> 'rotate',
										'value'	=> 'vrt'
									))?>
									<?=lang('rotate_flip_vert')?>
								</label>
							</li>
							<li class="rotate_hor">
								<label style="background-image: url(<?=PATH_CP_GBL_IMG?>it-horz-arrow.png)">
									<?=form_radio(array(
										'name'	=> 'rotate',
										'value'	=> 'hor'
									))?>
									<?=lang('rotate_flip_hor')?>
								</label>
							</li>
						</ul>
					</fieldset>
				</div> <!-- #image_tools -->
			<?php endif ?>
		</div> <!-- .panels -->
	<?=form_close()?>
</div> <!-- .upload_edit -->

<script>
	var file = <?= $file_json ?>;
	parent.$.ee_fileuploader.update_file(file);
</script>

<?php $this->embed('_shared/file_upload/iframe_footer') ?>
