<?php extend_template('default') ?>

<?php if (count($entries) < 1):?>
	<p class="notice"><?=lang('no_autosave_data')?></p>
<?php else:
	
	$this->table->set_heading($table_headings);

	foreach ($entries as $row)
	{
		$this->table->add_row($row);
	}
	echo $this->table->generate();
	$this->table->clear();

endif;?>