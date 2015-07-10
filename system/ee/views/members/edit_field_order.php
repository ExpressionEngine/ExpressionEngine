<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=update_field_order')?>

	<?php

	if (count($fields) > 0)
	{		
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
									lang('field_id'), 
									lang('fieldlabel'),
									lang('fieldname'),
									lang('edit_field_order')
								);

		foreach ($fields as $field)
		{
			$this->table->add_row(
									$field['id'],
									$field['label'],
									$field['name'],
									form_input($field)
								);
			
		}

		echo $this->table->generate();
	}

	?>
	
	<p><?=form_submit('', lang('update'), 'class="submit"')?></p>

<?=form_close()?>