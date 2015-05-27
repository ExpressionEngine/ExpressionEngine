<?php extend_template('default')?>

<h3><?=lang('category_group').': '.$group_name?></h3>

<?php
	$this->table->set_heading(
		array('data' => lang('id'), 'width' => '4%'),
		lang('field_label'),
		lang('field_name'),
		lang('field_type'),
		''
	);
							
	if (count($custom_fields) > 0)
	{
		foreach ($custom_fields as $field)
		{
			$this->table->add_row(
				$field['field_id'],
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_custom_category_field'.AMP.'group_id='.$group_id.AMP.'field_id='.$field['field_id'].'">'.$field['field_label'].'</a>',
				$field['field_name'],
				$field['field_type'],
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=delete_custom_category_field_confirm'.AMP.'group_id='.$group_id.AMP.'field_id='.$field['field_id'].'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_field_groups'), 'colspan' => 4));
	}
	
	echo $this->table->generate();
?>