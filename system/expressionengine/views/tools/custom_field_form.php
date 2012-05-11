<?php extend_template('default') ?>
		
<?=validation_errors(); ?>
<?=form_open('C=tools_utilities'.AMP.'M=create_custom_fields', '', $form_hidden)?>

	<p><?=lang('confirm_field_assignment_blurb')?></p>		

	<?php
	$heading[] = form_checkbox('select_all', 'true', set_checkbox('select_all', 'true'), 'class="toggle_all" id="select_all"').NBS.lang('create', 'select_all');
	$heading[] = '<span class="notice">*</span>'.lang('field_name', 'm_field_name');
	$heading[] = '<span class="notice">*</span>'.lang('field_label', 'm_field_label');

	$heading[] = lang('is_required');
	$heading[] = lang('is_public');
	$heading[] = lang('is_reg_form');			
	$heading[] = lang('order');			
	
				
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading($heading);
	
	$i = 0;

	foreach ($new_fields as $key => $value)
	{
		$this->table->add_row(
							form_checkbox('create_ids['.$i.']', 'y', '', ' class="toggle" id="create_box_'.$i.'"'),
							form_input(array('id'=>$i.'_m_field_name','name'=>'m_field_name['.$i.']',
								'class'=>'field','value'=>set_value('m_field_name['.$i.']', $value))).form_error('m_field_name['.$i.']'),
							form_input(array('id'=>$i.'_m_field_name','name'=>'m_field_label['.$i.']',
								'class'=>'field','value'=>set_value('m_field_name['.$i.']', $value))),
							form_checkbox('required['.$i.']', 'y', set_checkbox('required['.$i.']', 'y')),
							form_checkbox('public['.$i.']', 'y', set_checkbox('public['.$i.']', 'y')),
							form_checkbox('reg_form['.$i.']', 'y', set_checkbox('reg_form['.$i.']', 'y')),
							form_input(array('id'=>$i.'_m_field_order','name'=>'m_field_order['.$i.']','size'=>'3',
								'class'=>'field','value'=>set_value('m_field_order['.$i.']', $order_start+$i)))									
						);
		$i++;
	}
	?>

	<?=$this->table->generate()?>
	

	<p><?=form_submit('import_from_xml', lang('create'), 'class="submit"')?></p>

<?=form_close()?>