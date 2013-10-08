<?php extend_template('default') ?>

<?php
	// Local Plugins Table

	$heading = array();
	$heading[] = count($plugins).' '.lang('plugin_installed');
	$heading[] = lang('pi_version');
	
	if ($is_writable)
	{
		$heading[] = form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"');
	}

	$this->table->set_heading($heading);


	if (count($plugins) >= 1)
	{
		foreach ($plugins as $key => $plugin)
		{
			$row = array();
			$row[] = '<a href="'.BASE.AMP.'C=addons_plugins'.AMP.'M=info'.AMP.'name='.$key.'">'.$plugin['pi_name'].'</a>';
			$row[] = $plugin['pi_version'];
			if ($is_writable)
			{
				$row[] = form_checkbox('toggle[]', $key, FALSE, 'class="toggle"');
			}
			$this->table->add_row($row);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_plugins_exist'), 'colspan' => 2));
	}
?>
	
<?php
	if ($is_writable)
	{
		echo form_open('C=addons_plugins'.AMP.'M=remove_confirm');
	}
?>

<?=$this->table->generate()?>

<?php
	if ($is_writable)
	{
		echo '<p>'.form_submit('remove_plugins', lang('plugin_remove'), 'class="submit"').'</p>';
	}
?>

<?php
	if ($is_writable)
	{
		echo form_close();
	}
?>