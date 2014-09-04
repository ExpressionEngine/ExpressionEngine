<?php extend_template('default') ?>

<?php
	$this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
	$this->table->set_heading(
								lang('global_variables'),
								lang('global_variable_syntax'),
								lang('delete')
							);
							
	if ($global_variables_count >= 1)
	{
		foreach ($global_variables->result() as $variable)
		{
			$this->table->add_row(
				'<a href="'.BASE.AMP.'C=design'.AMP.'M=global_variables_update'.AMP.'variable_id='.$variable->variable_id.'">'.$variable->variable_name.'</a>', 
				'{'.$variable->variable_name.'}', 
				'<a href="'.BASE.AMP.'C=design'.AMP.'M=global_variables_delete'.AMP.'variable_id='.$variable->variable_id.'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_global_variables'), 'colspan' => 3));
	}
	
	echo $this->table->generate();
?>