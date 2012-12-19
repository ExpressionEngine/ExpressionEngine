<?php extend_template('default') ?>

<p><?=str_replace('%s', BASE.AMP.'C=design'.AMP.'M=global_variables', lang('snippets_explanation'))?></p>

<?php
	$this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
	$this->table->set_heading(
								lang('snippets'),
								lang('snippet_syntax'),
								lang('delete')
							);
							
	if ($snippets_count >= 1)
	{
		foreach ($snippets->result() as $variable)
		{
			$this->table->add_row(
				'<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_edit'.AMP.'snippet='.$variable->snippet_name.'">'.$variable->snippet_name.'</a>', 
				'{'.$variable->snippet_name.'}', 
				'<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_delete'.AMP.'snippet_id='.$variable->snippet_id.'">'.lang('delete').'</a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_snippets'), 'colspan' => 3));
	}
	
	echo $this->table->generate();
?>