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

			<div class="file_manager_toolbar">
				<?=form_open('C=content_files'.AMP.'M=edit_image', array('id'=>'image_edit_form'))?>

					<fieldset id="crop_fieldset" class="" style="width:30%; float:left">
						<legend><?=lang('crop')?></legend>
						<div style="width:48%; float:left">
							<?=lang('crop_width', 'crop_width')?>
							<?=form_input('width', $file_info['width'])?>
						</div>
						
						<div style="width:48%; float:right;">
							<?=lang('crop_height', 'crop_height')?>
							<?=form_input('height', $file_info['height'])?>
						</div>

						<div style="width:48%; float:left; margin-top:15px">
							<?=lang('crop_x', 'crop_x')?>
							<?=form_input()?>
						</div>
						
						<div style="width:48%; float:right; margin-top:15px">
							<?=lang('crop_y', 'crop_y')?>
							<?=form_input()?>
						</div>
					</fieldset>

					<fieldset id="rotate_fieldset" class="" style="width:30%; float:right">
						<legend><?=lang('rotate')?></legend>
						<?=lang('rotate', 'rotate')?>
						<ul>
							<li class="rotate_90"><img src="<?=$cp_theme_url?>images/image_edit_rotate_cw.png" alt="<?=lang('rotate_90r')?>" width="36" height="42" /></li>
							<li class="rotate_270"><img src="<?=$cp_theme_url?>images/image_edit_rotate_ccw.png" alt="<?=lang('rotate_90l')?>" width="36" height="42" /></li>
							<li class="rotate_vrt"><img src="<?=$cp_theme_url?>images/image_edit_flip_vert.png" alt="<?=lang('rotate_flip_vert')?>" width="36" height="42" /></li>
							<li class="rotate_hor"><img src="<?=$cp_theme_url?>images/image_edit_flip_hor.png" alt="<?=lang('rotate_flip_hor')?>" width="36" height="42" /></li>
						</ul>
					</fieldset>

					<fieldset id="resize_fieldset" class="" style="width:30%; margin-left:34%">
						<legend><?=lang('resize')?></legend>
						<div style="width:48%; float:left;">
							<?=lang('resize_width', 'resize_width')?>
							<?=form_input()?>
						</div>
						
						<div style="width:48%; float:right;">
							<?=lang('resize_height', 'resize_height')?>
							<?=form_input()?>
						</div>						
					</fieldset>
					<p class="submit_button"><?=form_submit('save_image', lang('save_image'), 'class="submit" id="edit_file_submit"')?> <?=form_submit('edit_done', lang('done'), 'class="submit" id="edit_done"')?></p>
					
				<?=form_close()?>
			</div>
			<img src="<?=$file_url?>" <?=$file_info['size_str']?>>
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