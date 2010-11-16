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
				<h2><?=lang('file_upload_prefs')?></h2>
		</div>
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>
		<div class="clear_left"></div>

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
										lang('current_upload_prefs'),
										array('data' => '', 'width' => '5%'),
										array('data' => '', 'width' => '5%')
									);
									
			if ($upload_locations->num_rows() > 0)
			{
				foreach ($upload_locations->result() as $upload_location)
				{
					$this->table->add_row(
						'<strong>'.$upload_location->id.' '.$upload_location->name.'</strong>',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences'.AMP.'id='.$upload_location->id.'" title="'.lang('edit').'"><img src="'.$cp_theme_url.'images/icon-edit.png" alt="'.lang('edit').'"</a>',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=delete_upload_preferences_conf'.AMP.'id='.$upload_location->id.'" title="'.lang('delete').'"><img src="'.$cp_theme_url.'images/icon-delete.png" alt="'.lang('delete').'" /></a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_upload_prefs'), 'colspan' => 3));
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