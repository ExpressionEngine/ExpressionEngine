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
				<h2><?=lang('file_upload_prefs')?>
					<?php $this->load->view('_shared/action_nav') ?>
				</h2>
		</div>
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>
		<div class="clear_left"></div>

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
				array('data' => lang('file_directory_id'), 'width' => '5%'),
				lang('current_upload_prefs'),
				array('data' => lang('edit'), 'width' => '5%'),
				array('data' => lang('delete'), 'width' => '5%'),
				array('data' => lang('sync'), 'width' => '5%')
			);
									
			if ($upload_locations->num_rows() > 0)
			{
				foreach ($upload_locations->result() as $upload_location)
				{
					$this->table->add_row(
						$upload_location->id,
						'<strong>'.$upload_location->name.'</strong>',
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_upload_preferences'.AMP.'id='.$upload_location->id.'" title="'.lang('edit').'"><img src="'.$cp_theme_url.'images/icon-edit.png" alt="'.lang('edit').'"</a>',
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_upload_preferences_conf'.AMP.'id='.$upload_location->id.'" title="'.lang('delete').'"><img src="'.$cp_theme_url.'images/icon-delete.png" alt="'.lang('delete').'" /></a>',
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=sync_directory'.AMP.'id='.$upload_location->id.'" title="'.lang('sync').'"><img src="'.PATH_CP_GBL_IMG.'database_refresh.png" alt="'.lang('sync').'" /><a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_upload_dirs_available'), 'colspan' => 5));
			}
			
			echo $this->table->generate();
		?>
		
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_upload_preferences.php */
/* Location: ./themes/cp_themes/default/admin/file_upload_preferences.php */
