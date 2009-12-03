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

		<div class="heading"><h2><?=$cp_page_title?></h2></div>
        
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>


		<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
										lang('status_group'),
//@todo build drag and drop order functionality										lang('order'),
										'',
										''
									);
									
			if ($statuses->num_rows() > 0)
			{
				foreach ($statuses->result() as $status)
				{
					$delete = ($status->status != 'open' AND $status->status != 'closed') ? '<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_delete_confirm'.AMP.'status_id='.$status->status_id.'"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.png" alt="'.lang('delete').'" width="19" height="18" /></a>' : '--';

					$this->table->add_row(
						$status->status,
//@todo build drag and drop order functionality						'',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_edit'.AMP.'status_id='.$status->status_id.'">'. lang('edit').'</a>',
						$delete
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_statuses')));
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

/* End of file add_edit_statuses.php */
/* Location: ./themes/cp_themes/corporate/admin/add_edit_statuses.php */