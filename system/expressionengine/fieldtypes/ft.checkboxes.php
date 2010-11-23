<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Option Group Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Checkboxes_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Checkboxes',
		'version'	=> '1.0'
	);
	
	var $has_array_data = TRUE;

	// used in display_field() below to set 
	// some defaults for third party usage
	var $settings_vars = array(
		'field_text_direction'	=> 'rtl',
		'field_pre_populate'	=> 'n',
		'field_list_items'		=> array(),
		'field_pre_field_id'	=> '',
		'field_pre_channel_id'	=> ''
	);

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Checkboxes_ft()
	{
		parent::EE_Fieldtype();
		$this->EE->load->helper('custom_field');
	}
	
	// --------------------------------------------------------------------
	
	function validate($data)
	{
		$text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';

		$this->settings['selected'] = $data;

		// in case another field type was here
		$field_options	= $this->_get_field_options($data);

		// If they've selected something we'll make sure that it's a valid choice
		$selected = $this->EE->input->post('field_id_'.$this->settings['field_id']);
	
		if ($selected)
		{
			if ( ! is_array($selected))
			{
				$selected = array($selected);
			}
		
			$selected = form_prep($selected);
			$unknown = array_diff($selected, array_keys($field_options));

			if (count($unknown) > 0)
			{
				// They tampered with the array, we'll drop the illegal values
				foreach($_POST['field_id_'.$this->settings['field_id']] as $idx => $validate)
				{
					if (in_array($validate, $unknown))
					{
						unset($_POST['field_id_'.$this->settings['field_id']][$idx]);
					}
				}
			}
			unset($unknown);
		}
	}
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		array_merge($this->settings, $this->settings_vars);

		$values = decode_multi_field($data);

		if (is_string($data) && $data != '' && $data[0] == '<')
		{
			return $data;
		}
				
		if (isset($this->settings['string_override']) && $this->settings['string_override'] != '')
		{
			return $this->settings['string_override'];
		}
		
		$field_options	= $this->_get_field_options($data);

		$values = decode_multi_field($data);

		$r = form_fieldset('');

		foreach($field_options as $option)
		{
			$checked = (in_array(form_prep($option), $values)) ? TRUE : FALSE;
			$r .= '<label>'.form_checkbox($this->field_name.'[]', $option, $checked).NBS.$option.'</label>';
		}
		return $r.form_fieldset_close();
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
		if (is_array($params) AND isset($params['limit']))
		{
			$limit = $params['limit'];
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
		$this->field_formatting_row($data, 'checkboxes');
		$this->multi_item_row($data, 'checkboxes');
	}
	
	function _get_field_options($data)
	{
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

			$this->EE->db->select('field_id_'.$this->settings['field_pre_field_id']);
			$this->EE->db->where('channel_id', $this->settings['field_pre_channel_id']);
			$pop_query = $this->EE->db->get('channel_data');

			if ($pop_query->num_rows() > 0)
			{
				foreach ($pop_query->result_array() as $prow)
				{
					if (trim($prow['field_id_'.$this->settings['field_pre_field_id']]) == '')
					{
					 	continue;
					}
					
					$selected = ($prow['field_id_'.$this->settings['field_pre_field_id']] == $data) ? 1 : '';
					$pretitle = substr($prow['field_id_'.$this->settings['field_pre_field_id']], 0, 110);
					$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
					$pretitle = form_prep($pretitle);

					$field_options[form_prep(trim($prow['field_id_'.$this->settings['field_pre_field_id']]))] = $pretitle;
				}
			}
		}
		
		return $field_options;
	}
}

// END Checkboxes_ft class

/* End of file ft.checkboxes.php */
/* Location: ./system/expressionengine/fieldtypes/ft.checkboxes.php */
