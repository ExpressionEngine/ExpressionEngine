<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
	
		// If they've selected something we'll make sure that it's a valid choice
		$selected = $this->EE->input->post($this->field_name);
	
		if ($selected)
		{
			if ( ! in_array($selected, array_keys($field_options)))
			{
				unset($_POST['field_id_'.$this->settings['field_id']]);
			}
		}
		
		
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
		$this->multi_item_row($data, 'radio');
	}
}

// END Radio_ft class

/* End of file ft.radio.php */
/* Location: ./system/expressionengine/fieldtypes/ft.radio.php */