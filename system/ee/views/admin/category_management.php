<?php extend_template('default') ?>

<?php
	$this->table->set_heading(
		array('data' => lang('id'), 'width' => '4%'),
		lang('group_name'),
		'',
		'',
		'',
		''
	);
							
	if (count($categories) > 0)
	{
		foreach ($categories as $group)
		{
			$this->table->add_row(
				'<strong>'.$group['group_id'].'</strong>',
				'<strong>'.$group['group_name'].'</strong>',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group['group_id'].'">'. lang('add_edit_categories').'</a> ('.$group['category_count'].')',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_category_group'.AMP.'group_id='.$group['group_id'].'">'.lang('edit_category_group').'</a>',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=category_custom_field_group_manager'.AMP.'group_id='.$group['group_id'].'">'. lang('manage_custom_fields').'</a> ('.$group['custom_field_count'].')',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=category_group_delete_conf'.AMP.'group_id='.$group['group_id'].'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_category_group_message'), 'colspan' => 6));
	}
	
	echo $this->table->generate();
?>