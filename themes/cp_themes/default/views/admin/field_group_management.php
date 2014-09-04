<?php extend_template('default') ?>
		
<?php
	$this->table->set_heading(
		lang('group_name'),
		'',
		''
	);
							
	if ($field_groups->num_rows() > 0)
	{
		foreach ($field_groups->result() as $field)
		{
			$this->table->add_row(
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_management'.AMP.'group_id='.$field->group_id.'">'.$field->group_name.'</a> ('.$field->count.')', 
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_group_edit'.AMP.'group_id='.$field->group_id.'">'.lang('rename').'</a>',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_group_delete_confirm'.AMP.'group_id='.$field->group_id.'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_field_group_message'), 'colspan' => 5));
	}
	
	echo $this->table->generate();
?>