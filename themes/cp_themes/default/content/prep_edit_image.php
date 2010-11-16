<?php
if ($EE_view_disable !== TRUE)
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
			<h2 class="edit"><?=$cp_page_title?></h2>
		</div>

		<?php $this->load->view('_shared/message');?>

		<div id="file_manager">

			<div id="file_manager_tools">
				<h3 class="closed" class="file_information_header"><a href="#"><?=lang('edit_modes')?></a></h3>
				<div id="file_information_hold" class="f_m_s">

					<p class="cp_button"><a href="#" id="crop_mode"><?=lang('crop_mode')?></a></p>
					<p class="cp_button"><a href="#" id="resize_mode"><?=lang('resize_mode')?></a></p>
					<p class="cp_button"><a href="#" id="rotate_mode"><?=lang('rotate_mode')?></a></p>

					<?=form_open('C=content_files'.AMP.'M=edit_image', array('id'=>'image_edit_form'), $form_hidden)?>
						<input type="hidden" name="ajax" value="FALSE" id="ajax" />
						<fieldset id="crop_fieldset" class="edit_option shun">
							<legend><?=lang('crop')?></legend>
							<p><?=lang('crop_width', 'crop_width')?> <?=form_input($crop_width)?></p>
							<p><?=lang('crop_height', 'crop_height')?> <?=form_input($crop_height)?></p>
							<p><?=lang('crop_x', 'crop_x')?> <?=form_input($crop_x)?></p>
							<p class="last"><?=lang('crop_y', 'crop_y')?> <?=form_input($crop_y)?></p>
						</fieldset>

						<fieldset id="resize_fieldset" class="edit_option shun">
							<legend><?=lang('resize')?></legend>
							<p><?=lang('resize_width', 'resize_width')?> <?=form_input($resize_width)?></p>
							<p class="last"><?=lang('resize_height', 'resize_height')?> <?=form_input($resize_height)?></p>
						</fieldset>

						<fieldset id="rotate_fieldset" class="edit_option shun">
							<legend><?=lang('rotate')?></legend>
							<p class="last"><?=lang('rotate', 'rotate')?> <?=form_dropdown('rotate', $rotate_options, $rotate_selected, 'id="rotate"')?></p>
							<ul class="icons">
								<li class="rotate_90"><img src="<?=$cp_theme_url?>images/image_edit_rotate_cw.png" alt="<?=lang('rotate_90r')?>" width="36" height="42" /></li>
								<li class="rotate_270"><img src="<?=$cp_theme_url?>images/image_edit_rotate_ccw.png" alt="<?=lang('rotate_90l')?>" width="36" height="42" /></li>
								<li class="rotate_vrt"><img src="<?=$cp_theme_url?>images/image_edit_flip_vert.png" alt="<?=lang('rotate_flip_vert')?>" width="36" height="42" /></li>
								<li class="rotate_hor"><img src="<?=$cp_theme_url?>images/image_edit_flip_hor.png" alt="<?=lang('rotate_flip_hor')?>" width="36" height="42" /></li>
							</ul>
						</fieldset>

						<p class="submit_button"><?=form_submit('edit_image', lang('edit_image'), 'class="submit" id="edit_file_submit"')?> <?=form_submit('edit_done', lang('done'), 'class="submit" id="edit_done"')?></p>

					<?=form_close()?>

				</div>
			</div>

			<div id="showToolbarLink">
				<a href="#"><span><?=lang('hide_toolbar')?></span>&nbsp;
					<img alt="<?=lang('hide')?>" id="hideToolbarImg" width="20" height="17" src="<?=$cp_theme_url?>images/content_hide_image_toolbar.png" style="display: inline" />
					<img alt="<?=lang('show')?>" id="showToolbarImg" width="20" height="17" src="<?=$cp_theme_url?>images/content_show_image_toolbar.png" class="js_hide" />
				</a>
			</div>
			
			<div id="file_manager_holder">
				<div class="main_tab solo" id="file_manager_list">

					<div id="edit_image_holder">
						<img src="<?=$url_path?>" alt="" id="edit_image" />
					</div>

					<div id='dialog_message' class="dialog" style='display:none; padding: 20px;'>
						<p class='message'><img alt="" width="16" height="16" src="<?=$cp_theme_url?>images/indicator.gif" /></p>
					</div>

				</div>
			</div><!-- close holder-->

			<div class="clear"></div>
		</div>
	</div><!-- contents -->
</div><!-- mainContent -->


<div id="confirm" style="display:none" title="<?=lang('apply_changes')?>">
	<div></div>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_browse.php */
/* Location: ./themes/cp_themes/default/tools/file_browse.php */