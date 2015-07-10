<?php extend_template('default') ?>

<div id="file_manager_toolbar" class="edit_controls">
	<?=form_open('C=content_files'.AMP.'M=edit_image', array('id'=>'image_crop_form'), $form_hiddens)?>
		<?= form_hidden('action', 'crop') ?>
		<h3 class="accordion"><?=lang('crop')?></h3>
		<div id="file_manager_crop">
			<ul>
				<li>
					<?=lang('crop_width', 'crop_width')?>
					<?=form_input('crop_width', $file_info['width'], 'id="crop_width" class="crop_dim"')?>
					<?=form_error('crop_width')?>
				</li>
				<li>
					<?=lang('crop_height', 'crop_height')?>
					<?=form_input('crop_height', $file_info['height'], 'id="crop_height" class="crop_dim"')?>
					<?=form_error('crop_height')?>
				</li>
				<li>
					<?=lang('crop_x', 'crop_x')?>
					<?=form_input('crop_x', 0, 'id="crop_x" class="crop_dim"')?>
					<?=form_error('crop_x')?>
				</li>
				<li>
					<?=lang('crop_y', 'crop_y')?>
					<?=form_input('crop_y', 0, 'id="crop_y"  class="crop_dim"')?>
					<?=form_error('crop_y')?>
				</li>
			</ul>
			<p class="submit_button">
				<a href="#" id="toggle_crop" class="submit js_show"><?=lang('crop_mode')?></a>
				<?=form_submit('save_image_crop', lang('save_image'), 'class="submit"')?><br />
				<a href="#" id="cancel_crop" style="display: none"><?=lang('cancel_changes')?></a>
			</p>
		</div>
	<?=form_close()?>
	<?=form_open('C=content_files'.AMP.'M=edit_image', array('id'=>'image_rotate_form'), $form_hiddens)?>
		<?= form_hidden('action', 'rotate') ?>
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
				<?=form_error('rotate')?>
				<?=form_submit('save_image_rotate', lang('save_image'), 'class="submit"')?><br />
				<?=anchor('#', lang('cancel_changes'), 'class="disabled"')?>
			</p>
		</div>
	<?=form_close()?>
	<?=form_open('C=content_files'.AMP.'M=edit_image', array('id'=>'image_resize_form'), $form_hiddens)?>
		<?= form_hidden('action', 'resize') ?>
		<h3 class="accordion"><?=lang('resize')?></h3>
		<div id="resize_fieldset">
			<ul>
				<li>
					<?=lang('resize_width', 'resize_width')?>
					<?=form_input('resize_width', $file_info['width'], 'id="resize_width"')?>
					<?=form_error('resize_width')?>
				</li>
				<li>
					<?=lang('resize_height', 'resize_height')?>
					<?=form_input('resize_height', $file_info['height'], 'id="resize_height"')?>
					<?=form_error('resize_height')?>
				</li>
			</ul>
			<p class="submit_button">
				<?=form_submit('save_image_resize', lang('save_image'), 'id="submit_resize" class="submit"')?><br />
				<?=anchor('#', lang('cancel_changes'), 'id="cancel_resize" style="display: none"')?>
			</p>
		</div>
		<div class="clear_left"></div>
	<?=form_close()?>
</div>
<div id="file_manager_edit_file">
	<img src="<?=$file_url?>?r=<?=$filemtime?>" <?=$file_info['size_str']?>>
</div> <!-- #file_manager_edit_file -->