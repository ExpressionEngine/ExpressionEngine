<?php extend_template('default') ?>

<?php
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading($headings);
?>
<div class="shun"><?=$this->table->generate($results)?></div>