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

			<?=form_open('C=admin_content'.AMP.'M=edit_upload_preferences', '', $form_hidden)?>
			<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(
											array('data' => lang('preference'), 'style' => 'width:50%;'),
											lang('setting')
										);
			
				//Upload Pref Name
				$this->table->add_row(array(
						'<strong>'.form_label('<span class="notice">*</span> '.lang('upload_pref_name'), 'name').'</strong>',
						form_error('name').
						form_input(array(
							'id'	=> 'name',
							'name'	=> 'name',
							'class'	=> 'fullfield',
							'value' => set_value('name', $field_name)
							)
						)
					)
				);
				
				// Server Path
				$this->table->add_row(array(
						'<strong>'.form_label('<span class="notice">*</span> '.lang('server_path'), 'server_path').'</strong>',
						form_error('server_path').
						form_input(array(
							'id' 	=> 'server_path',
							'name'	=> 'server_path',
							'class' => 'fullfield',
							'value' => set_value('server_path', $field_server_path)
							)
						)
					)
				);

				// URL to Upload Directory
				$this->table->add_row(array(
						'<strong>'.form_label('<span class="notice">*</span> '.lang('url_to_upload_dir'), 'url').'</strong>',
						form_error('url').
						form_input(array(
							'id'	=> 'url',
							'name'	=> 'url',
							'class' => 'fullfield',
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
						'<strong>'.form_label('<span class="notice">*</span> '.lang('allowed_types'), 'allowed_types').'</strong>',
						form_dropdown('allowed_types', $options, $allowed_types)
					)
				);
				
				foreach ($upload_pref_fields as $field)
				{
					$value = 'field_'.$field;
					
					$this->table->add_row(array(
							'<strong>'.lang($field, $field).'</strong>',
							form_error($field).
							form_input(array(
								'id'	=> $field,
								'name'	=> $field,
								'class'	=> 'fullfield',
								'value'	=> set_value($field, $$value)
								)
							)
						)
					);
				}
			
				echo $this->table->generate();
				$this->table->clear();
			?>
<p class="notice"><?=required().lang('required_fields')?></p>

				<h2 class="innerHeading"><?=lang('restrict_to_group')?></h2>

				<p class="instructional_notice ">
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

					if($upload_groups->num_rows() == 0)
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
				?>
				<?=$this->table->generate()?>

				

				<p class="centerSubmit"><?=form_submit('submit', lang($lang_line), 'class="submit"')?></p>

    		<?=form_close()?>
			
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_upload_create.php */
/* Location: ./themes/cp_themes/corporate/admin/file_upload_create.php */