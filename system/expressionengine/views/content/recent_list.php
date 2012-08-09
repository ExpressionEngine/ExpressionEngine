<?php extend_template('default') ?>

<?php if (count($entries) < 1):?>
	<p class="notice"><?=$no_result?></p>
<?php else:
	
	$this->table->set_heading($left_column, $right_column);

	foreach ($entries as $left => $right)
	{
		$this->table->add_row($left, $right);
	}
	echo $this->table->generate();
	$this->table->clear();

endif;?>