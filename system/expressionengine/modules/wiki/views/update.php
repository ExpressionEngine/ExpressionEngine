<?php 
	$this->table->set_heading(array(
			array('data' => lang('setting'), 'width' => '50%'),
			lang('preference')
		)
	);
?>

<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=update', '', $form_hidden)?>

	<?php 
	
		$this->table->add_row(array(
				lang('label_name', 'wiki_label_name'),
				form_error('wiki_label_name').
				form_input('wiki_label_name', set_value('wiki_label_name', $wiki_label_name_value), 'id="wiki_label_name"')
			)
		);
		
		$this->table->add_row(array(
				lang('short_name', 'wiki_short_name'),
				form_error('wiki_short_name').
				form_input('wiki_short_name', set_value('wiki_short_name', $wiki_short_name_value), 'id="wiki_short_name"')
			)
		);

		$this->table->add_row(array(
				lang('text_format', 'wiki_text_format'),
				form_error('wiki_text_format').
				form_dropdown('wiki_text_format', $wiki_text_format_options, set_value('wiki_text_format', $wiki_text_format_value), 'id="wiki_text_format"')
			)
		);
		

		$this->table->add_row(array(
				lang('html_format', 'wiki_html_format'),
				form_error('wiki_html_format').
				form_dropdown('wiki_html_format', $wiki_html_format_options, set_value('wiki_html_format', $wiki_html_format_value), 'id="wiki_html_format"')
			)
		);

		$this->table->add_row(array(
				lang('upload_dir', 'wiki_upload_dir'),
				form_error('wiki_upload_dir').
				form_dropdown('wiki_upload_dir', $wiki_upload_dir_options, set_value('wiki_upload_dir', $wiki_upload_dir_value), 'id="wiki_upload_dir"')
			)
		);

		$this->table->add_row(array(
				lang('admins', 'wiki_admins[]'),
				form_error('wiki_admins').
				form_multiselect('wiki_admins[]', $wiki_admins_options, set_value('wiki_admins', $wiki_admins_value), 'id="wiki_admins"')
			)
		);

		$this->table->add_row(array(
				lang('users', 'wiki_users[]'),
				form_error('wiki_users').
				form_multiselect('wiki_users[]', $wiki_users_options, set_value('wiki_users', $wiki_users_value), 'id="wiki_users"')
			)
		);

		$this->table->add_row(array(
				lang('revision_limit', 'wiki_revision_limit'),
				form_error('wiki_revision_limit').
				form_input('wiki_revision_limit', set_value('wiki_revision_limi', $wiki_revision_limit_value), 'id="wiki_revision_limit"')
			)
		);


		$this->table->add_row(array(
				lang('author_limit', 'wiki_author_limit'),
				form_error('wiki_author_limit').
				form_input('wiki_author_limit', set_value('wiki_author_limit', $wiki_author_limit_value), 'id="wiki_author_limit"')
			)
		);

		$this->table->add_row(array(
				lang('moderation_emails', 'wiki_moderation_emails'),
				form_error('wiki_moderation_emails').
				form_input('wiki_moderation_emails', set_value('wiki_moderation_emails', $wiki_moderation_emails_value), 'id="wiki_moderation_emails"')
			)
		);
		
		echo $this->table->generate();
		$this->table->clear()?>

	<h3><?=lang('namespaces')?></h3>
	<p><?=lang('namespaces_list_subtext')?></p>


	<h4><?=lang('namespaces_list')?></h4>

	<?php
		$this->table->set_heading(
			lang('namespace_label'),
			lang('namespace_short_name'),
			lang('admins'),
			lang('users'),
			''
		);

		if (count($namespaces) > 0){
			foreach($namespaces as $namespace)
			{
				$this->table->add_row(
										form_input('namespace_label_'.$namespace['namespace_id'], $namespace['namespace_label']),
										form_input('namespace_name_'.$namespace['namespace_id'], $namespace['namespace_name']),
										form_dropdown('namespace_admins_'.$namespace['namespace_id'].'[]', $member_group_options, $namespace['namespace_admins'], 'multiple="multiple"'),
										form_dropdown('namespace_users_'.$namespace['namespace_id'].'[]', $member_group_options, $namespace['namespace_users'], 'multiple="multiple"'),
										form_submit(array('name' => 'add_namespace', 'value' => '+', 'class' => 'submit')).' '.form_submit(array('name' => 'remove_namespace_'.$namespace['namespace_id'], 'value' => '-', 'class' => 'submit remove_namespace'))
									);
			}
		}

		// blank row for new values
		$this->table->add_row(
								form_input('namespace_label_'.$next_namespace_id, ''),
								form_input('namespace_name_'.$next_namespace_id, ''),
								form_dropdown('namespace_admins_'.$next_namespace_id.'[]', $member_group_options, '', 'multiple="multiple"'),
								form_dropdown('namespace_users_'.$next_namespace_id.'[]', $member_group_options, '', 'multiple="multiple"'),
								form_submit(array('name' => 'add_namespace', 'value' => '+', 'class' => 'submit'))
							);

		echo $this->table->generate();
	?>


	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
	</p>

<?=form_close()?>

<?php
/* End of file update.php */
/* Location: ./system/expressionengine/modules/wiki/views/update.php */
