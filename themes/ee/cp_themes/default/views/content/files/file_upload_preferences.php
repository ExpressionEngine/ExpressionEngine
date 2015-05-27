<?php extend_template('default') ?>

<?php
	$this->table->set_heading(
		array('data' => lang('file_directory_id'), 'width' => '5%'),
		lang('current_upload_prefs'),
		array('data' => lang('edit'), 'width' => '5%'),
		array('data' => lang('delete'), 'width' => '5%'),
		array('data' => lang('sync'), 'width' => '5%')
	);

	if (count($upload_locations) > 0)
	{
		foreach ($upload_locations as $upload_location)
		{
			$this->table->add_row(
				$upload_location['id'],
				'<strong>'.htmlentities($upload_location['name'], ENT_QUOTES).'</strong>',
				'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_upload_preferences'.AMP.'id='.$upload_location['id'].'" title="'.lang('edit').'"><img src="'.$cp_theme_url.'images/icon-edit.png" alt="'.lang('edit').'"</a>',
				'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_upload_preferences_conf'.AMP.'id='.$upload_location['id'].'" title="'.lang('delete').'"><img src="'.$cp_theme_url.'images/icon-delete.png" alt="'.lang('delete').'" /></a>',
				'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=sync_directory'.AMP.'id='.$upload_location['id'].'" title="'.lang('sync').'"><img src="'.PATH_CP_GBL_IMG.'database_refresh.png" alt="'.lang('sync').'" /><a>'
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_upload_dirs_available'), 'colspan' => 5));
	}

	echo $this->table->generate();
?>