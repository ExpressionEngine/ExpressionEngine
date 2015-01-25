<?php extend_template('default') ?>

<?php
	$this->table->set_heading(
		array('data' => lang('id'), 'width' => '4%'),
		lang('field_label'),
		lang('field_name'),
		lang('order'),
		lang('field_type'),
		''
	);
	
	if (count($custom_fields) > 0)
	{
		foreach ($custom_fields as $field)
		{
			$this->table->add_row(
				$field['field_id'],
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_edit'.AMP.'field_id='.$field['field_id'].AMP.'group_id='.$group_id.'">'.$field['field_label'].'</a>',
				form_input(array(
					'name'			=> 'field_name', 
					'readonly'		=> 'readonly',
					'value'			=> '{'.$field['field_name'].'}', 
					'class'			=> 'input-copy',
					'data-original'	=> '{'.$field['field_name'].'}'
				)),
				$field['field_order'],
				$field['field_type'],
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_delete_confirm'.AMP.'field_id='.$field['field_id'].AMP.'group_id='.$group_id.'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_field_groups'), 'colspan' => 6));
	}
	
	echo $this->table->generate();
?>