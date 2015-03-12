<?php extend_template('default') ?>

<?php
	$this->table->set_heading(
								lang('group_name'),
								'',
								''
							);
							
	if ($status_groups->num_rows() > 0)
	{
		foreach ($status_groups->result() as $status)
		{
			$this->table->add_row(
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_management'.AMP.'group_id='.$status->group_id.'">'.$status->group_name.'</a> ('.$status->count.')',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_group_edit'.AMP.'group_id='.$status->group_id.'">'.lang('rename').'</a>',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=status_group_delete_confirm'.AMP.'group_id='.$status->group_id.'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_status_group_message'), 'colspan' => 5));
	}
	
	echo $this->table->generate();
?>