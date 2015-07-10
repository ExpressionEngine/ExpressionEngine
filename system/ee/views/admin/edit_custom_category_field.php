<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=update_custom_category_fields', '', $form_hidden)?>
	<?php
		$this->table->set_heading(
									array('data' => lang('preference'), 'style' => 'width:50%;'),
									lang('setting')
								);			
		
		$this->table->add_row(array(
				required().lang('field_label', 'field_label').
				'<div class="subtext">'.lang('cat_field_label_info').'</div>',
				form_input(array('id'=>'field_label','name'=>'field_label','class'=>'field','value'=>$field_label))
			)
		);
		
		$this->table->add_row(array(
				required().lang('field_name', 'field_name').
				'<div class="subtext">'.lang('field_name_cont').'</div>',
				form_input(array('id'=>'field_name','name'=>'field_name','class'=>'field','value'=>$field_name))
			)
		);

		$this->table->add_row(array(
				lang('field_type', 'field_type'),
				form_dropdown('field_type', $field_type_options, $field_type, 'id="field_type"')
			)
		);

		$text_format = '<div class="field_format_option" id="text_format">'.
		lang('field_max_length', 'field_max1').'<br />'.
		form_input(array('id'=>'field_maxl','name'=>'field_maxl', 'size'=>4,'value'=>$field_maxl)).'</div>';

		$textarea_format = '<div class="field_format_option" id="textarea_format">'.
		lang('textarea_rows', 'field_ta_rows').'<br />'.
		form_input(array('id'=>'field_ta_rows','name'=>'field_ta_rows', 'size'=>4,'value'=>$field_ta_rows)).'</div>';

		$select_format = '<div class="field_format_option" id="select_format">'.
			lang('field_list_items', 'field_list_items').
			'<div class="subtext">'.lang('field_list_instructions').'</div>'.
			form_textarea(array('id'=>'field_list_items','name'=>'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>$field_list_items)).'</div>';


		$this->table->add_row(array(
				lang('field_options', 'field_options'),
				$text_format.$textarea_format.$select_format
			)
		);
		
		if ($update_formatting)
		{
			$warning = '<div class="formatting_notice_info notice">'.
						lang('update_existing_cat_fields', 'update_formatting').NBS.NBS.NBS.
						form_checkbox('update_formatting', 'y', FALSE, 'id="update_formatting"').'</div>';				
		}
		else
		{
			$warning = false;
		}
		
		$this->table->add_row(array(
				form_label(lang('deft_field_formatting'), 'field_default_fmt').$warning,
				form_dropdown('field_default_fmt', $field_default_fmt_options, $field_default_fmt, 'id="field_default_fmt"')
			)
		);
		
		$this->table->add_row(array(
				lang('show_formatting_buttons', 'show_formatting_buttons'),
				form_radio('field_show_fmt', 'y', $field_show_fmt_y, 'id="field_show_fmt_y"').NBS.NBS.
				lang('yes', 'field_show_fmt_y').'<br />'.
				form_radio('field_show_fmt', 'n', $field_show_fmt_n, 'id="field_show_fmt_n"').NBS.NBS.
				lang('no', 'field_show_fmt_n')
			)
		);

		$this->table->add_row(array(
				lang('text_direction', 'text_direction'),
				form_radio('field_text_direction', 'ltr', $field_text_direction_ltr, 'id="field_text_direction_ltr"').NBS.NBS.
				lang('ltr', 'field_text_direction_ltr').'<br />'.
				form_radio('field_text_direction', 'rtl', $field_text_direction_rtl, 'id="field_text_direction_rtl"').NBS.NBS.
				lang('rtl', 'field_text_direction_rtl')
			)
		);
		
		$this->table->add_row(array(
				lang('is_field_required', 'is_field_required'),
				form_radio('field_required', 'y', $field_required_y, 'id="field_required_y"').NBS.NBS.
				lang('yes', 'field_required_y').'<br />'.
				form_radio('field_required', 'n', $field_required_n, 'id="field_required_n"').NBS.NBS.
				lang('no', 'field_required_n')
			)
		);

		$this->table->add_row(array(
				lang('field_order', 'field_order'),
				form_input(array('id'=>'field_order','name'=>'field_order', 'size'=>4,'value'=>$field_order))
			)
		);
		
		echo $this->table->generate();
	?>

	<?=form_submit('custom_field_edit', lang($submit_lang_key), 'class="submit"')?>
<?=form_close()?>