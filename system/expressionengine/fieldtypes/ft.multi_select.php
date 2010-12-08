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
 * ExpressionEngine Multi-Select Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Multi_select_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Multi Select',
		'version'	=> '1.0'
	);
	
	var $has_array_data = TRUE;

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
		$this->EE->load->helper('custom_field');
		
		$values = decode_multi_field($data);
		$field_options = $this->_get_field_options($data);

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
		$this->field_formatting_row($data, 'multi_select');
		$this->multi_item_row($data, 'multi_select');
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

// END Multi_select_ft class

/* End of file ft.multi_select.php */
/* Location: ./system/expressionengine/fieldtypes/ft.multi_select.php */