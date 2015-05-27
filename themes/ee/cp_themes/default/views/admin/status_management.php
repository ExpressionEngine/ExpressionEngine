<?php extend_template('default');

	$this->table->set_heading(
		lang('id'),
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
				$status->status_id,
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
