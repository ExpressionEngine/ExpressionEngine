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
        
		<?php $this->load->view('_shared/message');?>

		<div class="heading"><h2><?=$cp_page_title?></h2></div>
		<div class="pageContents">

			<?=form_open('C=content_files'.AMP.'M=edit_upload_preferences', '', $form_hidden)?>
			<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
											lang('preference'),
											lang('setting')
										);
			
				//Upload Pref Name
				$this->table->add_row(array(
						form_label('<span class="notice">*</span> '.lang('upload_pref_name'), 'name'),
						form_error('name').
						form_input(array(
							'id'	=> 'name',
							'name'	=> 'name',
							'class'	=> 'field',
							'value' => set_value('name', $field_name)
							)
						)
					)
				);
				
				// Server Path
				$this->table->add_row(array(
						form_label('<span class="notice">*</span> '.lang('server_path'), 'server_path'),
						form_error('server_path').
						form_input(array(
							'id' 	=> 'server_path',
							'name'	=> 'server_path',
							'class' => 'field',
							'value' => set_value('server_path', $field_server_path)
							)
						)
					)
				);

				// URL to Upload Directory
				$this->table->add_row(array(
						form_label('<span class="notice">*</span> '.lang('url_to_upload_dir'), 'url'),
						form_error('url').
						form_input(array(
							'id'	=> 'url',
							'name'	=> 'url',
							'class' => 'field',
							'value'	=> set_value('url', $field_url)
							)
						)
					)
				);
			
				// Allowed File Types
				$options = array(
								'img' => lang('images_only'),
								'all'	=> lang('all_filetypes')
							);
				
				$this->table->add_row(array(
						form_label('<span class="notice">*</span> '.lang('allowed_types'), 'allowed_types'),
						form_dropdown('allowed_types', $options, $allowed_types)
					)
				);
				
				foreach ($upload_pref_fields1 as $field)
				{
					$value = 'field_'.$field;
					
					$this->table->add_row(array(
							lang($field, $field),
							form_error($field).
							form_input(array(
								'id'	=> $field,
								'name'	=> $field,
								'class'	=> 'field',
								'value'	=> set_value($field, $$value)
								)
							)
						)
					);
				}
				
								// Allowed File Types
				$options = array(
								'disallow' => lang('disallow_image'),
								'resize'	=> lang('resize_image')
							);
				
				
				foreach ($upload_pref_fields2 as $field)
				{
					$value = 'field_'.$field;
					
					$this->table->add_row(array(
							lang($field, $field),
							form_error($field).
							form_input(array(
								'id'	=> $field,
								'name'	=> $field,
								'class'	=> 'field',
								'value'	=> set_value($field, $$value)
								)
							)
						)
					);
				}
				
			
				echo $this->table->generate();
				$this->table->clear();
			?>
				<h3 style="margin-top: 15px"><?=lang('category_groups')?></h3>
				
				<?php if (count($cat_group_options) === 1):?>
					<?=sprintf(lang('no_assigned_category_groups'),
							   BASE.AMP.'C=admin_content'.AMP.'M=category_management')?>
				<?php else: ?>
				<p><?=lang('category_groups_text')?></p>
				<p><?=form_label(lang('category_group'), 'category_group')?><br>
					<?=form_dropdown('cat_group[]', $cat_group_options, $selected_cat_groups, 'multiple="multiple" style="min-width:200px; padding:3px"')?></p>
				<?php endif; ?>

				<h3 style="margin-top: 15px;"><?=lang('image_sizes')?></h3>
				<p class="noback"><?=lang('image_sizes_subtext')?></p>

				<div class="clear_left"></div>

				<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(
					lang('short_name'),
					lang('height'),
					lang('width'),
					lang('resize_type'),
					lang('wm_watermark'),
					''
					);

				$resize_options = array('constrain' => lang('constrain'), 'crop' => lang('crop'));
				
				if (count($image_sizes) > 0)
				{
					foreach($image_sizes as $size)
					{
						$this->table->add_row(
								form_input('size_short_name_'.$size['id'], $size['short_name']),
								form_input('size_height_'.$size['id'], $size['height']),
								form_input('size_width_'.$size['id'], $size['width']),
								form_dropdown('size_resize_type_'.$size['id'], $resize_options, $size['resize_type']),
								form_dropdown('size_watermark_id_'.$size['id'], $watermark_options, $size['watermark_id']),
								form_submit(array('name' => 'add_size', 'value' => '+', 'class' => 'submit')).' '.form_submit(array('name' => 'remove_size_'.$size['id'], 'value' => '-', 'class' => 'submit remove_size'))
								);
					}
				}

				// blank row for new values
				$this->table->add_row(
							form_input('size_short_name_'.$next_size_id, ''),
							form_input('size_height_'.$next_size_id, ''),
							form_input('size_width_'.$next_size_id, ''),
							form_dropdown('size_resize_type_'.$next_size_id, $resize_options, ''),
							form_dropdown('size_watermark_id_'.$next_size_id, $watermark_options, ''),
							form_submit(array('name' => 'add_image_size', 'value' => '+', 'class' => 'submit'))
							);

				echo $this->table->generate();
				$this->table->clear();
				?>
				
				<h3 style="margin-top: 15px;"><?=lang('restrict_to_group')?></h3>

				<p class="noback">
					<?=lang('restrict_notes_1')?><br />
					<span class="notice noback"><?=lang('restrict_notes_2')?></span>
				</p>
				
				<div class="clear_left"></div>
				<?php

					$this->table->set_template($cp_pad_table_template);
					$this->table->set_heading(
												lang('member_group'),
												lang('can_upload_files')
											);

					if ($upload_groups->num_rows() == 0)
					{
						$this->table->add_row(array('colspan'=>2, 'data'=>lang('no_results')));
					}
					else
					{  
						foreach ($upload_groups->result() as $group)
						{
							if (isset($_POST['access_'.$group->group_id]))
							{
								if ($_POST['access_'.$group->group_id] == 'y')
								{
									$value_yes = TRUE;
									$value_no = FALSE;
								}
								else
								{
									$value_yes = FALSE;
									$value_no = TRUE;
								}
							}
							else
							{
								if (in_array($group->group_id, $banned_groups))
								{
									$value_yes = FALSE;
									$value_no = TRUE;
								}
								else
								{
									$value_yes = TRUE;
									$value_no = FALSE;
								}
							}

							$this->table->add_row(
													$group->group_title,
													array(
														'class'	=> 'inline_labels',
														'data'	=>	lang('yes', 'access_y_'.$group->group_id).NBS.
																	form_radio('access_'.$group->group_id, 'y', $value_yes, 'id="access_y_'.$group->group_id.'"').NBS.NBS.NBS.
																	lang('no', 'access_n_'.$group->group_id).NBS.
																	form_radio('access_'.$group->group_id, 'n', $value_no, 'id="access_n_'.$group->group_id.'"')
													)
												);
						}
					}

					echo $this->table->generate();
				?>

				
				<p class="notice">* <?=lang('required_fields')?></p>

				<p><?=form_submit('submit', lang($lang_line), 'class="submit"')?></p>

    		<?=form_close()?>
			
        </div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_upload_create.php */
/* Location: ./themes/cp_themes/default/admin/file_upload_create.php */