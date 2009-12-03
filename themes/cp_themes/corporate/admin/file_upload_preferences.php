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

		<div class="heading"><h2><?=lang('file_upload_prefs')?></h2></div>
		
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>


		<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
				
										lang('ID'),
										'',
										lang('edit'),
										''
									);
									
			if ($upload_locations->num_rows() > 0)
			{
				foreach ($upload_locations->result() as $upload_location)
				{
					$this->table->add_row(
						'<strong>'.$upload_location->id.'</strong>',
						'<strong>'.$upload_location->name.'</strong>',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences'.AMP.'id='.$upload_location->id.'">'.lang('edit').'</a>',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=delete_upload_preferences_conf'.AMP.'id='.$upload_location->id.'"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.png" alt="'.lang('delete').'" width="19" height="18" /></a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_upload_prefs'), 'colspan' => 4));
			}
			
			echo $this->table->generate();
		?>
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_upload_preferences.php */
/* Location: ./themes/cp_themes/corporate/admin/file_upload_preferences.php */