<?php extend_template('default') ?>

<?=form_open('C=design'.AMP.'M=edit_template_group'.AMP.'tgpref='.$form_hidden['group_id'], '', $form_hidden)?>
	<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
			array('data' => lang('preference'), 'style' => 'width:50%;'),
			lang('setting')
		);
	
		// Name of Template Group
		$group_info = array(
			'id'		=> 'group_name',
			'name'		=> 'group_name',
			'size'		=> 30,
			'maxlength'	=> 50,
			'value'		=> set_value('group_name', $group_name)
		);
	
		$this->table->add_row(array(
				lang('name_of_template_group', 'name_of_template_group').'<br />'.
				lang('template_group_instructions').' '.lang('undersores_allowed'),
				form_error('group_name').
				form_input($group_info)
			)
		);
	
		// Is this the site index?
		$checkbox = array(
			'name'		=> 'is_site_default',
			'value'		=> 'y',
			'checked'	=> set_checkbox('is_site_default', 'y', $is_default)
		);

		$this->table->add_row(array(
				lang('is_site_default', 'is_site_default'),
				form_checkbox($checkbox)
			)
		);
	
		echo $this->table->generate();
	?>

	<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>

<?=form_close()?>