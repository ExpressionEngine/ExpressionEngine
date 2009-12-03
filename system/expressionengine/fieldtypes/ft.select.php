<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Select_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Select Dropdown',
		'version'	=> '1.0'
	);

	var $has_array_data = FALSE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Select_ft()
	{
		parent::EE_Fieldtype();
	}
	
	// --------------------------------------------------------------------
	
	function validate($data)
	{
		// @todo
	}
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';

		$selected = $data;

		$field_options = array();

		if ($this->settings['field_pre_populate'] == 'n')
		{
			if ( ! is_array($this->settings['field_list_items']))
			{
				foreach (explode("\n", trim($this->settings['field_list_items'])) as $v)
				{
					$v = trim($v);
					$field_options[$v] = $v;
				}
			}
			else
			{
				$field_options = $this->settings['field_list_items'];
			}
		}
		else
		{
			// We need to pre-populate this menu from an another channel custom field

			// @todo: model
			$this->EE->db->select('field_id_'.$row['field_pre_field_id']);
			$this->EE->db->where('channel_id', $row['field_pre_channel_id']);
			$pop_query = $this->EE->db->get('channel_data');

			$field_options[''] = '--';

			if ($pop_query->num_rows() > 0)
			{
				foreach ($pop_query->result_array() as $prow)
				{
					$selected = ($prow['field_id_'.$this->settings['field_pre_field_id']] == $data) ? 1 : '';
					$pretitle = substr($prow['field_id_'.$this->settings['field_pre_field_id']], 0, 110);
					$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
					$pretitle = form_prep($pretitle);

					$field_options[form_prep($prow['field_id_'.$this->settings['field_pre_field_id']])] = $pretitle;
				}
			}
		}
	
		// If they've selected something we'll make sure that it's a valid choice
		$selected = $this->EE->input->post($this->field_name);
	
		if ($selected)
		{
			if ( ! in_array($selected, array_keys($field_options)))
			{
				unset($_POST['field_id_'.$this->settings['field_id']]);
			}
		}

		return form_dropdown($this->field_name, $field_options, set_value($this->field_name, $this->settings['selected']), 'dir="'.$text_direction.'" id="'.$this->field_id.'"');
	}
	
	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = '', $tagdata = '')
	{
		return $this->EE->typography->parse_type(
			$this->EE->functions->encode_ee_tags($data),
			array(
				'text_format'	=> $this->row['field_ft_'.$this->field_id],
				'html_format'	=> $this->row['channel_html_formatting'],
				'auto_links'	=> $this->row['channel_auto_link_urls'],
				'allow_img_url' => $this->row['channel_allow_img_urls']
			)
		);
	}
	
	// --------------------------------------------------------------------

	function display_settings($data)
	{
		
		$this->EE->load->helper('custom_field_helper');
		create_multi_select_thing($data);
		
		/*
		
		$this->EE->table->add_row(
			'<p class="field_format_option select_format">'.
				form_radio('field_pre_populate', 'n', $data['field_pre_populate_n'], 'id="field_pre_populate_n"').
				lang('field_populate_manually', 'field_pre_populate_n').BR.
				form_radio('field_pre_populate', 'y', $data['field_pre_populate_y'], 'id="field_pre_populate_y"').
				lang('field_populate_from_channel', 'field_pre_populate_y').
			'</p>',
			'<p class="field_format_option select_format_n">'.
				lang('field_list_items', 'select_list_items').
				lang('multi_list_items', 'multi_select_list_items').BR.
				lang('field_list_instructions').BR.
				form_textarea(array('id'=>'field_list_items','name'=>'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>$data['field_list_items'])).
			'</p>
			<p class="field_format_option select_format_y">'.
				lang('select_channel_for_field', 'field_pre_populate_id').
				form_dropdown('field_pre_populate_id', $data['field_pre_populate_id_options'], $data['field_pre_populate_id_select'], 'id="field_pre_populate_id"').
			'</p>'
		);
		
		*/
	}

	// --------------------------------------------------------------------

	function save_settings($data)
	{
		// nothin'
	}
}

// END Select_ft class

/* End of file ft.select.php */
/* Location: ./system/expressionengine/fieldtypes/ft.select.php */