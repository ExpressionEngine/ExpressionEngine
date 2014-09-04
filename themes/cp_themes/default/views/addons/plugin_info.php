<?php extend_template('default') ?>

<?php
	$this->table->set_heading(lang('plugin_information'), '');

	foreach($plugin as $key => $data)
	{
		$this->table->add_row(
			lang($key) ? lang($key) : ucwords(str_replace("_", " ", $key)),
			$data
		);
	}

	echo $this->table->generate();
?>