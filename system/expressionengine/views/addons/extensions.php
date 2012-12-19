<?php extend_template('default') ?>

<?php
	$this->table->set_heading(
								lang('extension_name'),
								lang('settings'),
								lang('documentation'),
								lang('version'),
								lang('status')
							);
						
	if (count($extension_info) >= 1)
	{
		foreach ($extension_info as $filename => $extension)
		{
			$this->table->add_row(
				$extension['name'],
				$extension['settings_enabled'] ? '<a href="'.$extension['settings_url'].'">'.lang('settings').'</a>' : $extension['no_settings'],
				$extension['documentation'] ? '<a href="'.$extension['documentation'].'" rel="external">'.lang('documentation').'</a>' : '--',
				$extension['version'],
				($extensions_enabled) ? lang($extension['status']).' (<a href="'.BASE.AMP.'C=addons_extensions'.AMP.'M=toggle_extension'.AMP.'which='.$filename.'">'.lang($extension['status_switch']).'</a>)' : lang($extension['status'])
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_extensions_exist'), 'colspan' => 4));
	}

	echo $this->table->generate();
?>