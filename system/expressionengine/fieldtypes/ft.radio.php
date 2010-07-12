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
 * ExpressionEngine Radio Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Radio_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Radio Buttons',
		'version'	=> '1.0'
	);

	var $has_array_data = FALSE;

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
	function Radio_ft()
	{
		parent::EE_Fieldtype();
	}
	
	// --------------------------------------------------------------------
	
	function validate($data)
	{
		$valid			= FALSE;
		$field_options	= $this->_get_field_options($data);
		
		if ($data === FALSE OR $data == '')
		{
			return TRUE;
		}
		
		$data = form_prep($data);

		foreach($field_options as $key => $val)
		{
			if (is_array($val))
			{
				if (array_key_exists($data, $val))
				{
					$valid = TRUE;
					break;
				}
			}
			elseif ((string) $key === $data)
			{
				$valid = TRUE;
				break;
			}
		}
		
		if ( ! $valid)
		{
			return $this->EE->lang->line('invalid_selection');
		}
	}
	
	// --------------------------------------------------------------------

	function display_field($data)
	{
		array_merge($this->settings, $this->settings_vars);

		$text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';

		$field_options = $this->_get_field_options($data);
		
		// If they've selected something we'll make sure that it's a valid choice
		$selected = $data;
//$this->EE->input->post($this->field_name);
		
		$r = form_fieldset('');

		foreach($field_options as $option)
		{
			$selected = ($option == $data);
			$r .= '<label>'.form_radio($this->field_name, $option, $selected).NBS.$option.'</label>';
		}
		
		return $r.form_fieldset_close();
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
		$this->field_formatting_row($data, 'radio');
		$this->multi_item_row($data, 'radio');
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
					$field_options[form_prep($v)] = form_prep($v);
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
		
		return $field_options;
	}
}

// END Radio_ft class

/* End of file ft.radio.php */
/* Location: ./system/expressionengine/fieldtypes/ft.radio.php */
