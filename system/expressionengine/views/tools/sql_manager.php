<?php extend_template('default') ?>

<?php
	$this->table->set_heading(array('data' => lang('sql_info'), 'width' => '50%'), lang('value'));

	foreach ($sql_info as $name => $value)
	{
		$this->table->add_row(
								"<strong>".lang($name)."</strong>",
								"{$value}"
							);

	}

	echo $this->table->generate();
?>