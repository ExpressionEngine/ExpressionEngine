<?php extend_template('default') ?>

<?=validation_errors(); ?>
<?=form_open('C=tools_utilities'.AMP.'M=confirm_data_form', '', $form_hidden)?>

	<p><?=lang('assign_fields_blurb')?></p>		
	<p class="alert"><?=lang('password_field_warning')?></p>	


	<p><?=lang('required_fields')?></p>
	<?php
	$heading[] = lang('your_data');
	$heading[] = lang('member_fields');
	
	if (count($custom_select_options) > 1)
	{
		$heading[] = lang('custom_member_fields');
	}
	
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading($heading);

	$i=0;
	foreach ($fields[0] as $key => $value)
	{
		if (count($custom_select_options) > 1)
			{
				$this->table->add_row(											
									$value,
									form_dropdown('field_'.$i, $select_options),
									form_dropdown('c_field_'.$i, $custom_select_options)
							);
			}
			else
			{
				$this->table->add_row(	
									$value,
									form_dropdown('field_'.$i, $select_options)										
							);
			}

		$i++;
	}
	?>

	<?=$this->table->generate()?>
	

	<p class="field_format_option select_format">
			<?=form_checkbox('encrypt', 'y', set_checkbox('encrypt', 'y', $encrypt))?>
			<?=lang('plaintext_passwords', 'encrypt')?><br />

		</p>

	<p><?=form_submit('map', lang('map_elements'), 'class="submit"')?></p>

<?=form_close()?>