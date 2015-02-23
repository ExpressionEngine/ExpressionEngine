<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=update_category_group', '', $form_hidden)?>
	<?php
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
		lang('preference'),
		lang('setting')
	);

	$group_name = form_input(array(
		'id' => 'group_name',
		'name' => 'group_name',
		'class' => 'field',
		'value' => set_value('group_name', $group_name)
	));
	if ($error = form_error('group_name'))
	{
		$group_name .= '<span class="notice">'.$error.'</span>';
	}
	$this->table->add_row(array(
			lang('name_of_category_group', 'group_name'),
			$group_name
		)
	);

	$this->table->add_row(array(
			lang('cat_field_html_formatting', 'field_html_formatting'),
			form_dropdown(
				'field_html_formatting',
				$formatting_options,
				set_value('field_html_formatting', $field_html_formatting)
			)
		)
	);

	$setting = '';

	if (count($can_edit_checks) == 0)
	{
		$setting = '<br /><span class="notice">'.str_replace('%x', strtolower(lang('edit')), lang('no_member_groups_available')).'<a class="less_important_link" title="'.lang('member_groups').'" href="'.BASE.AMP.'C=members'.AMP.'M=member_group_manager">'.lang('member_groups').'</a></span>';
	}
	else
	{
		foreach($can_edit_checks as $check)
		{
			$checked = (set_checkbox('can_edit_categories[]', $check['id'], $check['checked']) !== '');
			$setting .= '<br /><label>'
				.form_checkbox(
					'can_edit_categories[]',
					$check['id'],
					$checked
				)
				.' '
				.$check['value'].'</label>';
		}
	}

	$this->table->add_row(array(
			lang('can_edit_categories', 'can_edit_categories'),
			$setting
		)
	);

	$setting = '';

	if (count($can_delete_checks) == 0)
	{
		$setting .= '<br /><span class="notice">'.str_replace('%x', strtolower(lang('delete')), lang('no_member_groups_available')).
				   '<a class="less_important_link" title="'.lang('member_groups').'" href="'.BASE.AMP.'C=members'.AMP.'M=member_group_manager">'.lang('member_groups').'</a></span>';
	}
	else
	{
		foreach ($can_delete_checks as $check)
		{
			$checked = (set_checkbox('can_delete_categories[]', $check['id'], $check['checked']) !== '');
			$setting .= '<br /><label>'
				.form_checkbox(
					'can_delete_categories[]',
					$check['id'],
					$checked
				)
				.' '
				.$check['value'].'</label>';
		}
	}

	$this->table->add_row(array(
			lang('can_delete_categories', 'can_delete_categories'),
			$setting
		)
	);

	$options = array(
		0 => lang('none'),
		1 => lang('exclude_from_publish'),
		2 => lang('exclude_from_files')
	);

	$this->table->add_row(array(
			lang('exclude_from_channels_or_publish', 'exclude_group'),
			form_dropdown(
				'exclude_group',
				$options,
				set_value('exclude_group', $exclude_selected)
			)
		)
	);

	echo $this->table->generate();
	?>

	<?=form_submit('submit', lang('submit'), 'class="submit"')?>

<?=form_close()?>
