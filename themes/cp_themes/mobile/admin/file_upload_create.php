<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="translate" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=admin_system"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<?=form_open('C=admin_content'.AMP.'M=edit_upload_preferences', '', $form_hidden)?>

	<div class="label">
		<?=form_label('<span class="notice">*</span> '.lang('upload_pref_name'), 'name')?>
		<?=form_error('name')?>
	</div>
	<ul>
		<li>
		<?=form_input(array(
			'id'	=> 'name',
			'name'	=> 'name',
			'class'	=> 'field',
			'value' => set_value('name', $field_name)
			)
		)?></li>
	</ul>
	
	<div class="label">
		<?=form_label('<span class="notice">*</span> '.lang('server_path'), 'server_path')?>
		<?=form_error('server_path')?>
	</div>
	<ul>
		<li><?=form_input(array(
			'id' 	=> 'server_path',
			'name'	=> 'server_path',
			'class' => 'field',
			'value' => set_value('server_path', $field_server_path)
			)
		)?></li>
	</ul>	
	
	<div class="label">
		<?=lang('url_to_upload_dir', 'url')?>
		<?=form_error('url')?>
	</div>
	<ul>
		<li><?=form_input(array(
			'id'	=> 'url',
			'name'	=> 'url',
			'class' => 'field',
			'value'	=> set_value('url', $field_url)
			)
		)?></li>
	</ul>
	<?php
	// Allowed File Types
	$options = array(
					'img' => lang('images_only'),
					'all'	=> lang('all_filetypes')
				);
	?>
	<div class="label">
		<?=form_label('<span class="notice">*</span> '.lang('allowed_types'), 'allowed_types')?>
	</div>
	<ul>
		<li><?=form_dropdown('allowed_types', $options, $allowed_types)?></li>
	</ul>
	<?php foreach ($upload_pref_fields as $field):
			
			$value = 'field_'.$field;
	?>
		<div class="label">
			<?=lang($field, $field)?>
			<?=form_error($field)?>
		</div>
		<ul>
			<li><?=form_input(array(
				'id'	=> $field,
				'name'	=> $field,
				'class'	=> 'field',
				'value'	=> set_value($field, $$value)
				)
			)?></li>
		</ul>	
			
		<?php endforeach; 
	
	?>


		<h3 style="margin-top: 15px; margin-bottom:0" class="pad"><?=lang('restrict_to_group')?></h3>

		<p class="pad" style="margin-top:0">
			<?=lang('restrict_notes_1')?>
		</p>
		<p class="pad">
			<?=lang('restrict_notes_2')?>
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
				echo '<p class="pad container">'.lang('no_results').'</p>';
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
					
					?>
					<div class="label">
						<label><?=$group->group_title?></label>
					</div>
					<ul>
						<li><?=lang('yes', 'access_y_'.$group->group_id).NBS.
									form_radio('access_'.$group->group_id, 'y', $value_yes, 'id="access_y_'.$group->group_id.'"').'<br />'.
									lang('no', 'access_n_'.$group->group_id).NBS.NBS.NBS.
									form_radio('access_'.$group->group_id, 'n', $value_no, 'id="access_n_'.$group->group_id.'"')?></li>
					</ul>
					<?php
				}
			}
		?>

		<p class="notice pad"><?=required().lang('required_fields')?></p>

		<?=form_submit('submit', lang($lang_line), 'class="whiteButton"')?>

	<?=form_close()?>
	
	
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_upload_create.php */
/* Location: ./themes/cp_themes/mobile/admin/file_upload_create.php */