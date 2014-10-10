<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=update_profile_fields'.AMP.'U=1', '', $hidden_form_fields)?>
	<?php

	$notice = '<span class="notice">*</span> ';

	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
		array('data' => lang('preference'), 'style' => 'width:50%;'),
		lang('setting')
	);

	// Field Name
	$this->table->add_row(array(
			$notice.form_label(lang('fieldname'), 'm_field_name').'<br />'.lang('fieldname_cont').form_error('m_field_name'),
			form_input('m_field_name', set_value('m_field_name', $m_field_name), 'class="field"')
		)
	);

	// Field Label
	$this->table->add_row(array(
			$notice.form_label(lang('fieldlabel'), 'm_field_label').'<br />'.lang('for_profile_page').form_error('m_field_label'),
			form_input('m_field_label', set_value('m_field_label', $m_field_label), 'class="field"')
		)
	);

	// Field Description
	$this->table->add_row(array(
			form_label(lang('field_description'), 'm_field_description').'<br />'.lang('field_description_info').form_error('m_field_description'),
			form_input('m_field_description', set_value('m_field_description', $m_field_description), 'class="field" id="m_field_description"')
		)
	);

	// Field Order
	$this->table->add_row(array(
			form_label(lang('field_order'), 'm_field_order'),
			form_input('m_field_order', set_value('m_field_order', $m_field_order), 'class="field" id="m_field_order"')
		)
	);

	// Field Width
	$this->table->add_row(array(
			form_label(lang('field_width'), 'm_field_width'),
			form_input('m_field_width', set_value('m_field_width', $m_field_width), 'class="field" id="m_field_width"')
		)
	);

	//Field Type

	// Left Side:
	$left_side = form_label(lang('field_type'), 'm_field_type').'<br />'.
		form_dropdown('m_field_type', $m_field_type_options, set_value('m_field_type', $m_field_type), "onchange='showhide_element(this.options[this.selectedIndex].value);'");

	// Select Block
	$right_side = '<p id="select_block" style="display: '.$select_js.'">'.
				   form_label(lang('pull_down_items'), 'm_field_list_items').'<br />'.
				   form_textarea(array(
					   'id'    => 'm_field_list_items',
					   'name'  => 'm_field_list_items',
					   'cols'  => 90,
					   'rows'  => 10,
					   'class' =>
					   'field',
					   'value' => set_value('m_field_list_items', $m_field_list_items))).
				   '</p>';

	// Text Block
	$right_side .= '<p id="text_block" style="display: '.$text_js.';">'.
					lang('m_max_length', 'm_field_maxl').'<br />'.
					form_input('m_field_maxl', set_value('m_field_maxl', $m_field_maxl), 'class="field" id="m_field_maxl"').
					'</p>';

	// Textarea Block
	$right_side .= '<p id="textarea_block" style="display: '.$textarea_js.';">'.
					lang('text_area_rows', 'm_field_ta_rows').'<br />'.
					form_input(array(
						'id'    => 'm_field_ta_rows',
						'name'  => 'm_field_ta_rows',
						'class' => 'field',
						'value' => set_value('m_field_ta_rows', $m_field_ta_rows))).
					'</p>';

	$this->table->add_row(array(
		$left_side,
		$right_side
		)
	);

	// Text Formatting
	$this->table->add_row(array(
			lang('field_format', 'm_field_fmt').'<br />'.
			lang('text_area_rows_cont'),
			form_dropdown('m_field_fmt', $m_field_fmt_options, set_value('m_field_fmt', $m_field_fmt))
		)
	);

	// Required Field?
	$this->table->add_row(array(
			lang('is_field_required', 'm_field_required'),
			form_radio(array(
				'name'        => 'm_field_required',
				'id'          => 'm_field_required_y',
				'value'       => 'y',
				'checked'     => ($m_field_required == 'y') ? TRUE : FALSE)).NBS.
			lang('yes', 'm_field_required_y').repeater(NBS, 5).
			form_radio(array(
				'name'        => 'm_field_required',
				'id'          => 'm_field_required_n',
				'value'       => 'n',
				'checked'     => ($m_field_required == 'y') ? FALSE : TRUE)).NBS.
			lang('no', 'm_field_required_n')
	   )
	);

	// Visible in Public Profiles?
	$this->table->add_row(array(
			lang('is_field_public', 'm_field_public').'<br />'.
			lang('is_field_public_cont'),
			form_radio(array(
				'name'        => 'm_field_public',
				'id'          => 'm_field_public_y',
				'value'       => 'y',
				'checked'     => ($m_field_public == 'y') ? TRUE : FALSE)).NBS.
			lang('yes', 'm_field_public_y').repeater(NBS, 5).
			form_radio(array(
				'name'        => 'm_field_public',
				'id'          => 'm_field_public_n',
				'value'       => 'n',
				'checked'     => ($m_field_public == 'y') ? FALSE : TRUE)).NBS.
			lang('no', 'm_field_public_n')
	   )
	);

	// Visible in Registration Page?
	$this->table->add_row(array(
		   lang('is_field_reg', 'm_field_reg'),
			form_radio(array(
				'name'        => 'm_field_reg',
				'id'          => 'm_field_reg_y',
				'value'       => 'y',
				'checked'     => ($m_field_reg == 'y') ? TRUE : FALSE)).NBS.
			lang('yes', 'm_field_reg_y').repeater(NBS, 5).
			form_radio(array(
				'name'        => 'm_field_reg',
				'id'          => 'm_field_reg_n',
				'value'       => 'n',
				'checked'     => ($m_field_reg == 'y') ? FALSE : TRUE)).NBS.
			lang('no', 'm_field_reg_n')
	   )
	);

   // Visible in Control Panel Registration Page?
	$this->table->add_row(array(
		   lang('is_field_cp_reg', 'm_field_cp_reg'),
			form_radio(array(
				'name'        => 'm_field_cp_reg',
				'id'          => 'm_field_cp_reg_y',
				'value'       => 'y',
				'checked'     => ($m_field_cp_reg == 'y') ? TRUE : FALSE)).NBS.
			lang('yes', 'm_field_cp_reg_y').repeater(NBS, 5).
			form_radio(array(
				'name'        => 'm_field_cp_reg',
				'id'          => 'm_field_cp_reg_n',
				'value'       => 'n',
				'checked'     => ($m_field_cp_reg == 'y') ? FALSE : TRUE)).NBS.
			lang('no', 'm_field_cp_reg_n')
	   )
	);

	echo $this->table->generate();
	?>

	<p><span class="notice">*</span> <?=lang('required_fields')?></p>

	<p><?=form_submit('', $submit_label, 'class="submit"')?></p>

<?=form_close()?>
