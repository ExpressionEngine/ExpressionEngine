<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading">
			<h2 class="edit"><?=lang('content_files')?></h2>
		</div>
		
		<div class="pageContents group">
			<div id="file_manager_toolbar">
				<?=form_open('C=content_files'.AMP.'M=edit_image', array('id'=>'image_edit_form'))?>
					<h3 class="accordion"><?=lang('crop')?></h3>
					<div id="file_manager_crop">
						<ul>
							<li>
								<?=lang('crop_width', 'crop_width')?>
								<?=form_input('crop_width', $file_info['width'], 'id="crop_width"')?>
							</li>
							<li>
								<?=lang('crop_height', 'crop_height')?>
								<?=form_input('crop_height', $file_info['height'], 'id="crop_height"')?>
							</li>
							<li>
								<?=lang('crop_x', 'crop_x')?>
								<?=form_input('crop_x', '', 'id="crop_x"')?>
							</li>
							<li>
								<?=lang('crop_y', 'crop_y')?>
								<?=form_input('crop_y', '', 'id="crop_y"')?>
							</li>
							<li>
								<a href="#" id="toggle_crop"><?=lang('crop_mode')?></a>
							</li>
							<li style="display:none; float:left">
								<a href="#" id="save_crop"><?=lang('save_crop')?></a>
							</li>
							<li style="display:none; float:right">
								<a href="#" id="cancel_crop"><?=lang('cancel_crop')?></a>
							</li>
						</ul>
					</div>
					
					<h3 class="accordion"><?=lang('rotate')?></h3>
					<div id="rotate_fieldset">
						<ul>
							<li class="rotate_90">
								<a href="#">
									<?=lang('rotate_90r')?>
								</a>
							</li>
							<li class="rotate_270">
								<a href="#">
									<?=lang('rotate_90l')?>
								</a>
							</li>
							<li class="rotate_vrt">
								<a href="#">
									<?=lang('rotate_flip_vert')?>
								</a>
							</li>
							<li class="rotate_hor">
								<a href="#">
									<?=lang('rotate_flip_hor')?>
								</a>
							</li>
						</ul>
					</div>
					
					<h3 class="accordion"><?=lang('resize')?></h3>
					<div id="resize_fieldset">
						<ul>
							<li>
								<?=lang('resize_width', 'resize_width')?>
								<?=form_input('resize_width', $file_info['width'], 'id="resize_width"')?>
							</li>
							<li>
								<?=lang('resize_height', 'resize_height')?>
								<?=form_input('resize_height', $file_info['height'], 'id="resize_height"')?>
							</li>
						</ul>
					</div>
					<div class="clear_left"></div>
					
					<p class="submit_button">
						<?=form_submit('save_image', lang('save_image'), 'class="submit" id="edit_file_submit"')?><br />
						<?=anchor('#', lang('cancel_changes'), 'class="disabled"')?>
					</p>
				<?=form_close()?>
			</div>
			<div id="file_manager_edit_file">
				<img src="<?=$file_url?>" <?=$file_info['size_str']?>>
			</div> <!-- #file_manager_edit_file -->
			<div class="clear"></div>
		</div>
	</div>
</div>

<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_browse.php */
/* Location: ./themes/cp_themes/default/tools/file_browse.php */