<div id="file_manager" style="display:none;">
	<div id="file_manager_body">
		
		<div id="nav_wreaper">
			<ul id="main_navi">
				<li id="main_navi_0"><?=lang("upload_file")?></li>
			<?php foreach ($filemanager_directories as $dir_id => $dir_name):?>
				<li id="main_navi_<?=$dir_id?>"><?=$dir_name?></li>
			<?php endforeach;?>
			</ul>
		</div>
	</div>



	<div id="file_manager_main">
		<div id="pages">

			<div class="page" style="margin: 10px;">
				<div class="vertscrollable" id="page_0">
					<div class="items">

							<h3><?=lang("upload_file")?></h3>

							<iframe id='target_upload' name='target_upload' src='about:blank' style='width:200px;height:50px;border:1;display:none;'></iframe>

							<?=form_open_multipart($filemanager_backend_url.'&action=upload', array('target'=>'target_upload','id'=>'upload_form'))?>
								<input type="hidden" name="frame_id" value="target_upload" id="frame_id" />

							<p>
							<?php if (count($filemanager_directories) > 1):?>
								<?=form_label(lang('upload_dir_choose'), 'upload_dir')?>&nbsp;
								<?=form_dropdown('upload_dir', $filemanager_directories, '', 'id="upload_dir"')?>
							<?php else:
								reset($filemanager_directories); // force us to be on the first array key
							?>
								<input type="hidden" name="upload_dir" value="<?=key($filemanager_directories)?>" id="upload_dir" />
							<?php endif;?>
							</p>

							<div class="shun"></div>

							<p>
								<?=form_label(lang('upload_file'), 'upload_file', array("style"=>"display:none;"))?>
								<?=form_upload(array('id'=>'upload_file','name'=>'userfile','size'=>20,'class'=>'field'))?>
							</p>

							<div class="shun"></div>

							<p><button class="submit submit_alt"><img src="<?=$cp_theme_url?>images/upload_item.png" width="12" height="14" alt="<?=lang('upload')?>" />&nbsp;&nbsp;<?=lang('upload')?></button></p>

							<p id="progress"><img src="<?=$cp_theme_url?>images/indicator_ECF1F4.gif" /><br /><?=lang('loading')?>...</p>

							<?=form_close()?>

					</div>
				</div>
			</div>


			<?php foreach ($filemanager_directories as $dir_id => $name):?>
				<div class="page">
					<div class="vertscrollable" id="page_<?=$dir_id?>">
						<div class="items">
							<!-- this area gets dynamically filled -->
						</div>
					</div>

					<div id="nav_controls_<?=$dir_id?>" class="nav_controls navi_area">
						<a class="prevThumbs"></a>
						<div class="navi"></div>
						<a class="newThumbs"></a>
						<div class="clear"></div>
					</div>
					
				</div>
			<?php endforeach;?>

		</div>
	</div>

	<div class="image_edit_form_options" style="display:none;">
		<?=form_open($filemanager_backend_url.'&action=edit_image', array('id'=>'image_edit_form'))?>
			<input type="hidden" name="is_ajax" value="TRUE" id="is_ajax" />
			<input type="hidden" name="file" value="" id="file" />

			<fieldset id="resize_fieldset" class="edit_option shun">
				<legend><?=lang('resize')?></legend>
				<p><?=lang('resize_width', 'resize_width')?> <?=form_input('resize_width', '', 'id="resize_width" size="6"')?> <?=lang('pixels')?></p>
				<p class="last"><?=lang('resize_height', 'resize_height')?> <?=form_input('resize_height', '', 'id="resize_height" size="6"')?> <?=lang('pixels')?></p>
				<div><label><?=form_checkbox('constrain', 'constrain', TRUE, 'id="constrain"')?> <?=lang('constrain_proportions')?></label></div>
			</fieldset>
			<fieldset id="rotate_fieldset" class="edit_option shun">
				<legend><?=lang('rotate')?></legend>
				<ul class="icons">
					<li id="" class="rotate_90r"><img src="<?=$cp_theme_url?>images/image_edit_rotate_cw.png" alt="<?=lang('rotate_90r')?>" width="36" height="42" /></li>
					<li class="rotate_90l"><img src="<?=$cp_theme_url?>images/image_edit_rotate_ccw.png" alt="<?=lang('rotate_90l')?>" width="36" height="42" /></li>
					<li class="rotate_flip_vert"><img src="<?=$cp_theme_url?>images/image_edit_flip_vert.png" alt="<?=lang('rotate_flip_vert')?>" width="36" height="42" /></li>
					<li class="rotate_flip_hor"><img src="<?=$cp_theme_url?>images/image_edit_flip_hor.png" alt="<?=lang('rotate_flip_hor')?>" width="36" height="42" /></li>
				</ul>
			</fieldset>

			<div class="source">
				<label><?=form_radio('source', 'copy', TRUE)?> <?=lang('create_thumb_copy')?></label>
			</div>
			<div class="shun source">
				<label><?=form_radio('source', 'resize_orig', FALSE)?> <?=lang('resize_original')?></label>
			</div>

			<p class="submit_button"><?=form_submit('edit', lang('edit_image'), 'class="submit" id="edit"')?> <?=form_button('cancel', lang('cancel'), 'class="submit place_image"')?></p>

		<?=form_close()?>
	</div>
</div>