<?php extend_template('default') ?>

<?php
	if ($channel_data !== FALSE)
	{
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
			lang('channel_full_name'),
			lang('channel_short_name'),
			'',
			'',
			''
		);

		foreach ($channel_data->result() as $channel)
		{
			$this->table->add_row(
				"<strong>".htmlentities($channel->channel_title, ENT_QUOTES)."</strong>",
				form_input(array(
					'name'			=> 'field_name',
					'readonly'		=> 'readonly',
					'value'			=> $channel->channel_name,
					'class'			=> 'input-copy',
					'data-original'	=> $channel->channel_name
				)),
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=channel_edit'.AMP.'channel_id='.$channel->channel_id.'">'.lang('edit_preferences').'</a>',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=channel_edit_group_assignments'.AMP.'channel_id='.$channel->channel_id.'">'.lang('edit_groups').'</a>',
				'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=channel_delete_confirm'.AMP.'channel_id='.$channel->channel_id.'">'.lang('delete').'</a>'
			);
		}

		echo $this->table->generate();
	}
	else
	{
		$this->lang->load('content');
		echo lang('no_channels');
	}
?>
