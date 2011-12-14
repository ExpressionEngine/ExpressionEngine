<?php $this->load->view('_shared/file_upload/iframe_header'); ?>

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
					<ul>
						<li><label><input type="radio" name="image_tool" value="" checked /> <?=lang('no_change')?></label></li>
						<li>
							<label><input type="radio" name="image_tool" value="resize" /> <?=lang('resize')?></label>
							<div class="group">
								<ul>
									<li>
										<?=lang('resize_height', 'resize_height')?>
										<?=form_input('resize_height', $file['dimensions'][0], 'id="resize_height"')?>
									</li>
									<li>
										<?=lang('resize_width', 'resize_width')?>
										<?=form_input('resize_width', $file['dimensions'][1], 'id="resize_width"')?>
									</li>
								</ul>
								<p><?=anchor('#', lang('cancel_changes'), 'id="cancel_resize" style="display: none"')?></p>
							</div>
						</li>
						<li>
							<label><input type="radio" name="image_tool" value="rotate" /> <?=lang('rotate')?></label>
							<div class="group">
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
							</div>
						</li>
					</ul>
				</div> <!-- #image_tools -->
			<?php endif ?>
		</div> <!-- .panels -->
	<?=form_close()?>
</div> <!-- .upload_edit -->

<?php $this->load->view('_shared/file_upload/iframe_footer') ?>