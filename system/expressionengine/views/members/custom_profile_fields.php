<?php extend_template('default') ?>

<?php if ($fields->num_rows() > 0):

	$this->table->set_heading(
		lang('field_id'), 
		lang('fieldlabel'), 
		lang('fieldname'), 
		lang('order'), 
		'',
		''
	);

	foreach ($fields->result() as $field)
	{
		$this->table->add_row(
						$field->m_field_id,
						$field->m_field_label,
						$field->m_field_name,
						$field->m_field_order,
						'<a href="'.BASE.AMP.'C=members'.AMP.'M=edit_profile_field'.AMP.'m_field_id='.$field->m_field_id.'">'.lang('edit').'</a>',
						'<a href="'.BASE.AMP.'C=members'.AMP.'M=delete_profile_field_conf'.AMP.'m_field_id='.$field->m_field_id.'">'.lang('delete').'</a>'
						);					
	}

	echo $this->table->generate();
?>	

	<p class="shun"><a href="<?=BASE.AMP.'C=members'.AMP.'M=edit_field_order'?>"><?=lang('edit_field_order')?></a></p>

<?php else:?>
	<p class="notice"><?=lang('no_custom_profile_fields')?></p>
<?php endif;?>