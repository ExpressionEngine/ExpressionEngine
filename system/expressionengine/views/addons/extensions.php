<?php extend_template('default') ?>

<?php
if (isset($extensions_disabled))
{
	echo '<p>'.$extensions_disabled.'</p>';
}
else
{
	$this->table->set_heading(
		lang('extension_name'),
		lang('settings'),
		lang('documentation'),
		lang('version'),
		lang('status'),
		lang('action')
	);

	if (count($extension_info) >= 1)
	{
		foreach ($extension_info as $filename => $extension)
		{
			$this->table->add_row(
				$extension['name'],
				$extension['settings'],
				$extension['documentation'],
				$extension['version'],
				$extension['status'],
				$extension['actions']
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_extensions_exist'), 'colspan' => 4));
	}

	echo $this->table->generate();
}
?>