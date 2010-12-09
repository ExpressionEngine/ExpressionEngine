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
			<h2 class="edit"><?=lang('content_files')?></h2>
		</div>

		<div id="file_manager">

			<div id="file_manager_tools">
				<h3 class="closed file_information_header"><a href="#"><?=lang('file_information')?></a></h3>
				<div id="file_information_hold">

					<?php $this->load->view('content/_assets/file_sidebar_info');?>

				</div>

				<h3 class="closed"><a href="#"><?=lang('file_upload')?></a></h3>
				<div id="file_upload_hold" class="f_m_s">
					<iframe id='target_upload' name='target_upload' src='' style='width:200px;height:50px;border:1;display:none;'></iframe>
					<?=form_open_multipart('C=content_files'.AMP.'M=upload_file', array('id'=>'upload_form'))?>

					<p>
					<?php if (count($upload_directories) > 1):?>
						<?=form_label(lang('upload_dir_choose'), 'upload_dir')?>
						<?=form_dropdown('upload_dir', $upload_directories, '', 'id="upload_dir"')?>
					<?php else:?>
						<input type="hidden" name="upload_dir" value="<?=key($upload_directories)?>" id="upload_dir" />
					<?php endif;?>
					</p>
					
					<p>
						<?=form_label(lang('upload_file'), 'upload_file', array('class' => 'visualEscapism'))?>
						<?=form_upload(array('id'=>'upload_file','name'=>'userfile','size'=>15,'class'=>'field'))?>
					</p>

					<p class="custom_field_add"><button class="submit submit_alt"><img src="<?=$cp_theme_url?>images/upload_item.png" width="12" height="14" alt="<?=lang('upload')?>" />&nbsp;&nbsp;<?=lang('upload')?></button></p>

					<p id="progress"><img src="<?=$cp_theme_url?>images/indicator.gif" alt="<?=lang('loading')?>..." /><br /><?=lang('loading')?>...</p>

					<?=form_close()?>

				</div>

				<h3><a href="#"><?=lang('file_tools')?></a></h3>
				<div class="f_m_s">
					<ul>
						<li id="download_selected">
							<a href="#" title="<?=lang('download_selected')?>"><?=lang('download_selected')?></a>
						</li>
						<li id="delete_selected_files">
							<a title="<?=lang('delete_selected_files')?>" href="<?=BASE.AMP."C=content_files".AMP."M=delete_files_confirm"?>"><?=lang('delete_selected_files')?></a>
						</li>
						<?php if ($can_edit_upload_prefs):?>
						<li id="create_new_upload_pref">
							<a title="<?=lang('create_new_upload_pref')?>" href="<?=BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences'?>"><?=lang('create_new_upload_pref')?></a>
						</li>
						<li id="upload_item">
							<a id="upload_prefs" title="<?=lang('file_upload_prefs')?>" href="<?=BASE.AMP."C=admin_content".AMP."M=file_upload_preferences"?>"><?=lang('file_upload_prefs')?></a>
						</li>
						<?php endif;?>
					</ul>
				</div>
			</div>

			<div id="showToolbarLink">
				<a href="#"><span><?=lang('hide_toolbar')?></span>&nbsp;
					<img alt="<?=lang('hide')?>" id="hideToolbarImg" src="<?=$cp_theme_url?>images/content_hide_image_toolbar.png" style="display: inline" />
					<img alt="<?=lang('show')?>" id="showToolbarImg" src="<?=$cp_theme_url?>images/content_show_image_toolbar.png" class="js_hide" />
				</a>
			</div>
			
			<div id="file_manager_holder">
				<div class="main_tab solo" id="file_manager_list">

				<?php $this->load->view('_shared/message');?>

				<?php if (count($file_list) == 0):?>

						<p class="notice"><?=lang('no_upload_dirs')?></p>

				<?php else:?>

					<?=form_open('C=content_files'.AMP.'M=delete_files_confirm', array('id'=>'files_form'))?>

					<?php 
						$this->table->set_template($cp_pad_table_template);

						foreach ($file_list as $directory_info):
					?>

							<h3><?=$directory_info['name']?></h3>

							<div id="dir_id_<?=$directory_info['id']?>" style="display:<?=$directory_info['display']?>; margin-bottom:10px">
							<?php
								// without the div above, the slide effect breaks the table widths

								$this->table->set_heading(
															array('data' => lang('file_name'), 'style' => 'width:35%'),
															array('data' => lang('file_size'), 'style' => 'width:5%;'),
															array('data' => lang('kind'), 'style' => 'width:10%'),
															array('data' => lang('date'), 'style' => 'width:25%'),
															array('data' => lang('edit'), 'style' => 'width:5%'),
															array('data' => '', 'style' => 'width:7%'),
															array('data' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'), 'style' => 'width:2%', 'class' => 'file_select')
														);

								// no results?  Give the "no files" message
								if (count($directory_info['files']) == 0)
								{
									$this->table->add_row(array('data' => lang('no_uploaded_files'), 'colspan' => 7, 'class' => 'no_files_warning'));
								}
								else
								{
									// Create a row for each file
									foreach ($directory_info['files'] as $file)
									{
										$this->table->add_row($file);
									}
								}
								echo $this->table->generate();
								$this->table->clear(); // needed to reset the table
							?>
							</div>

					<?php endforeach;?>
					<input type="submit" class="submit" value="<?=lang('delete_selected_files')?>" />
					<?=form_close()?>

				<?php endif;?>

				<div class="clear"></div>
			</div>

			</div><!-- close holder-->

			<div class="clear"></div>
		</div>
	</div><!-- contents -->
</div><!-- mainContent -->

<div class="image_overlay" id="overlay" style="display:none"><a class="close"></a>
	<div class="contentWrap"></div>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_browse.php */
/* Location: ./themes/cp_themes/default/tools/file_browse.php */