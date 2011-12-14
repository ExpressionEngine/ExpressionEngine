<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=status_group_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>

			<?php
				$this->table->set_heading(
											lang('status_group'),
											'',
											''
										);

				if ($statuses->num_rows() > 0)
				{
					foreach ($statuses->result() as $status)
					{
						$delete = ($status->status != 'open' AND $status->status != 'closed') ? '<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_delete_confirm'.AMP.'status_id='.$status->status_id.'">'. lang('delete').'</a>' : '--';

					$status_name = ($status->status == 'open' OR $status->status == 'closed') ? lang($status->status) : $status->status;
						$this->table->add_row(
							$status_name,
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





</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file status_management.php */
/* Location: ./themes/cp_themes/default/admin/status_management.php */