<?php extend_template('default') ?>

<?=form_open($form_action)?>
	<?php if (isset($return_loc)):
		echo form_hidden(array('return_location' => $return_loc));
	endif; ?>
	<?php
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
								array('data' => lang('preference'), 'style' => 'width:50%;'),
								lang('setting')
							);

	foreach ($fields as $name => $details)
	{
		$this->table->add_row(
							"<strong>".lang($name, $name)."</strong>".(($details['subtext'] != '') ? "<div class='subtext'>{$details['subtext']}</div>" : ''),
							form_preference($name, $details)
							);
	}

	echo $this->table->generate();
	?>
	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
<?=form_close()?>
