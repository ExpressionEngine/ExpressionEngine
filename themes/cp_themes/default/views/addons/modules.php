<?php extend_template('default') ?>

<?php
	$this->table->set_heading($table_headings);
	echo $this->table->generate($modules);
?>