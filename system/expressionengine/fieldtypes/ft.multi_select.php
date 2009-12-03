<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Multi_select_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Multi Select',
		'version'	=> '1.0'
	);
	
	var $has_array_data = FALSE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Multi_select_ft()
	{
		parent::EE_Fieldtype();
	}
	
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$values = decode_multi_field($data);
		
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
		
		return form_multiselect($this->field_name.'[]', $field_options, $values, 'dir="'.$this->settings['field_text_direction'].'" id="'.$this->field_id.'"');
	}
	
	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$this->EE->load->helper('custom_field');
		$data = decode_multi_field($data);
		
		if ($tagdata)
		{
			return $this->_parse_multi($data, $params, $tagdata);
		}
		else
		{
			return $this->_parse_single($data, $params);
		}
	}
	
	// --------------------------------------------------------------------
	
	function _parse_single($data, $params)
	{
		if (isset($params['limit']))
		{
			$limit = intval($params['limit']);

			if (count($data) > $limit)
			{
				$data = array_slice($data, 0, $limit);
			}
		}

		if (isset($params['markup']) && ($params['markup'] == 'ol' OR $params['markup'] == 'ul'))
		{
			$entry = '<'.$params['markup'].'>';

			foreach($data as $dv)
			{
				$entry .= '<li>';
				$entry .= $dv;
				$entry .= '</li>';
			}

			$entry .= '</'.$params['markup'].'>';
		}
		else
		{
			$entry = implode(', ', $data);
		}

		return $this->EE->typography->parse_type(
				$this->EE->functions->encode_ee_tags($entry),
				array(
						'text_format'	=> $this->row['field_ft_'.$this->field_id],
						'html_format'	=> $this->row['channel_html_formatting'],
						'auto_links'	=> $this->row['channel_auto_link_urls'],
						'allow_img_url' => $this->row['channel_allow_img_urls']
					  )
		);
	}
	
	// --------------------------------------------------------------------
	
	function _parse_multi($data, $params, $tagdata)
	{
		$chunk = '';
		$limit = FALSE;
		
		// Limit Parameter
		if (is_array($chk_data[1]) AND isset($chk_data[1]['limit']))
		{
			$limit = $chk_data[1]['limit'];
		}

		foreach($data as $key => $item)
		{
			if ( ! $limit OR $key < $limit)
			{
				$vars['item'] = $item;
				$vars['count'] = $key + 1;	// {count} parameter

				$tmp = $this->EE->functions->prep_conditionals($tagdata, $vars);
				$chunk .= $this->EE->functions->var_swap($tmp, $vars);
			}
			else
			{
				break;
			}
		}

		// Everybody loves backspace
		if (isset($params['backspace']))
		{
			$chunk = substr($chunk, 0, - $params['backspace']);
		}
		
		// Typography!
		return $this->EE->typography->parse_type(
						$this->EE->functions->encode_ee_tags($chunk),
						array(
								'text_format'	=> $this->row['field_ft_'.$this->field_id],
								'html_format'	=> $this->row['channel_html_formatting'],
								'auto_links'	=> $this->row['channel_auto_link_urls'],
								'allow_img_url' => $this->row['channel_allow_img_urls']
							  )
		);
	}
	
	function display_settings($data)
	{
		$this->EE->load->helper('custom_field_helper');
//		create_multi_select_thing($data);
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
	
	function save_settings($data)
	{
		// nothing
	}
}

// END Multi_select_ft class

/* End of file ft.multi_select.php */
/* Location: ./system/expressionengine/fieldtypes/ft.multi_select.php */