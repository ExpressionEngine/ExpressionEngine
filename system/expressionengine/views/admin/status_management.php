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

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>
		<div class="clear_left"></div>

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
										lang('status_name'),
										''
									);
									
			if ($statuses->num_rows() > 0)
			{
				foreach ($statuses->result() as $status)
				{
					$delete = ($status->status != 'open' AND $status->status != 'closed') ? '<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_delete_confirm'.AMP.'status_id='.$status->status_id.'">'. lang('delete').'</a>' : '--';
					
					$status_name = ($status->status == 'open' OR $status->status == 'closed') ? lang($status->status) : $status->status;

					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_edit'.AMP.'status_id='.$status->status_id.'">'.$status_name.'</a>',
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

		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file add_edit_statuses.php */
/* Location: ./themes/cp_themes/default/admin/add_edit_statuses.php */