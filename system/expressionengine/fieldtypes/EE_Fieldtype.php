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
 * ExpressionEngine EE_Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Fieldtype {

	var $EE;
	var $field_id;
	var $field_name;
	var $settings = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function EE_Fieldtype()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	function _init($config = array())
	{
		foreach($config as $key => $val)
		{
			$this->$key = $val;
		}
	}
	
	// --------------------------------------------------------------------
	
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if ($tagdata)
		{
			return $tagdata;
		}
		
		return $data;
	}

	// --------------------------------------------------------------------
	
	function pre_process($data)
	{
		return $data;
	}
	
	// --------------------------------------------------------------------
	
	function validate()
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function save_global_settings()
	{
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Field Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	function display_settings($data)
	{
		return '';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Save Field
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function save($data)
	{
		return $data;
	}
	


	// --------------------------------------------------------------------
	
	/**
	 * Called after field is saved
	 *
	 * @access	public
	 * @param	string
	 */
	function post_save($data)
	{
		// $this->settings['entry_id'];
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Called when entries are deleted
	 *
	 * @access	public
	 * @param	mixed
	 */
	function delete($ids)
	{
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Save Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function save_settings($data)
	{
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Settings Modify Column
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	function settings_modify_column($data)
	{
		// Default custom field additions to channel_data
		$fields['field_id_'.$data['field_id']] = array(
			'type' 			=> 'text',
			'null'			=> TRUE
			);

		$fields['field_ft_'.$data['field_id']] = array(
			'type' 			=> 'tinytext',
			'null'			=> TRUE,
			);			
		
		return $fields;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Save Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function post_save_settings($data)
	{
		return;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Install
	 *
	 * @access	public
	 * @return	array	global settings
	 */
	function install()
	{
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * @access	public
	 * @return	void
	 */
	function uninstall()
	{
		return;
	}

	// --------------------------------------------------------------------
	
	function display_publish_field($data)
	{
		$tmp = $this->EE->load->_ci_view_path;
		$this->EE->load->_ci_view_path = PATH_THEMES.'cp_themes/default/';

		$vars['glossary_items'] = $this->EE->load->view('content/_assets/glossary_items', '', TRUE);
		
		$this->EE->load->_ci_view_path = $tmp;
		
		$this->EE->load->vars($vars);
		return $this->display_field($data);
	}
	
	// --------------------------------------------------------------------
	
	function field_formatting_row($data, $prefix = FALSE)
	{
		$edit_format_link = $data['edit_format_link'];
		$prefix = ($prefix) ? $prefix.'_' : '';
		
		$extra = '';
		
		if ($data['field_id'] != '')
		{
			$extra .= '<div class="notice update_formatting js_hide">';
			$extra .= '<p>'.lang('fmt_has_changed').'</p><p>';
			$extra .= form_checkbox($prefix.'update_formatting', 'y', FALSE, 'id="'.$prefix.'update_formatting"');
			$extra .= NBS.lang('update_existing_fields', $prefix.'update_formatting');
			$extra .= '</p></div>';
		}

		$this->EE->table->add_row(
			lang('deft_field_formatting', $prefix.'field_fmt'),
			form_dropdown($prefix.'field_fmt', $data['field_fmt_options'], $data['field_fmt'], 'id="'.$prefix.'field_fmt"').
				NBS.$data['edit_format_link'].BR.BR.
				'<strong>'.lang('show_formatting_buttons').'</strong>'.BR.
				form_radio($prefix.'field_show_fmt', 'y', $data['field_show_fmt_y'], 'id="'.$prefix.'field_show_fmt_y"').NBS.
				lang('yes', 'field_show_fmt_y').NBS.NBS.NBS.NBS.NBS.
				form_radio($prefix.'field_show_fmt', 'n', $data['field_show_fmt_n'], 'id="'.$prefix.'field_show_fmt_n"').NBS.
				lang('no', 'field_show_fmt_n').
				$extra
		);
		
		$this->EE->javascript->output('
		$("#'.$prefix.'field_fmt").change(function() {
			$(this).nextAll(".update_formatting").show();
		});
		');
	}
	
	// --------------------------------------------------------------------
	
	function text_direction_row($data, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		$this->EE->table->add_row(
			'<strong>'.lang('text_direction').'</strong>',
			form_radio($prefix.'field_text_direction', 'ltr', $data['field_text_direction_ltr'], 'id="field_text_direction_ltr"').NBS.
				lang('ltr', 'field_text_direction_ltr').NBS.NBS.NBS.NBS.NBS.
				form_radio($prefix.'field_text_direction', 'rtl', $data['field_text_direction_rtl'], 'id="field_text_direction_rtl"').NBS.
				lang('rtl', 'field_text_direction_rtl')
		);		
	}
	
	// --------------------------------------------------------------------
	
	function field_content_type_row($data, $prefix = FALSE)
	{
		$suf = $prefix;
		$prefix = ($prefix) ? $prefix.'_' : '';

		$extra = '';

		if ($data['field_id'] != '')
		{
			$extra .= '<div class="notice update_content_type js_hide">';
			$extra .= '<p>'.sprintf(
								lang('content_type_changed'), 
								$data['field_content_'.$suf]).'</p></div>';
		}
		
		$this->EE->table->add_row(
			lang('field_content_'.$suf, 'field_content_'.$suf),
			form_dropdown($prefix.'field_content_type', $data['field_content_options_'.$suf], $data['field_content_'.$suf], 'id="'.$prefix.'field_content_type"').$extra
		);	
		
		$this->EE->javascript->output('
		$("#'.$prefix.'field_content_type").change(function() {
			$(this).nextAll(".update_content_type").show();
		});
		');
					
	}

	// --------------------------------------------------------------------
	
	function multi_item_row($data, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		$this->EE->table->add_row(
			'<p class="field_format_option select_format">'.
				form_radio($prefix.'field_pre_populate', 'n', $data['field_pre_populate_n'], 'id="'.$prefix.'field_pre_populate_n"').NBS.
				lang('field_populate_manually', $prefix.'field_pre_populate_n').BR.
				form_radio($prefix.'field_pre_populate', 'y', $data['field_pre_populate_y'], 'id="'.$prefix.'field_pre_populate_y"').NBS.
				lang('field_populate_from_channel', $prefix.'field_pre_populate_y').
			'</p>',
			'<p class="field_format_option select_format_n">'.
				lang('multi_list_items', $prefix.'multi_select_list_items').BR.
				lang('field_list_instructions').BR.
				form_textarea(array('id'=>$prefix.'field_list_items','name'=>$prefix.'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>$data['field_list_items'])).
			'</p>
			<p class="field_format_option select_format_y">'.
				lang('select_channel_for_field', $prefix.'field_pre_populate_id').
				form_dropdown($prefix.'field_pre_populate_id', $data['field_pre_populate_id_options'], $data['field_pre_populate_id_select'], 'id="'.$prefix.'field_pre_populate_id"').
			'</p>'
		);
	
		$this->EE->javascript->click('#'.$prefix.'field_pre_populate_n', '$(".select_format_n").show();$(".select_format_y").hide();', FALSE);
		$this->EE->javascript->click('#'.$prefix.'field_pre_populate_y', '$(".select_format_y").show();$(".select_format_n").hide();', FALSE);
		
		// When this field becomes active for the first time - hit the option we need
		$this->EE->javascript->output('
			$("#ft_'.rtrim($prefix, '_').'").one("activate", function() {
				$("#'.$prefix.'field_pre_populate_'.$data['field_pre_populate'].'").trigger("click");
			});
		');
	}
	
	// --------------------------------------------------------------------
	
	function field_show_smileys_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_smileys', 'field_show_smileys', $prefix);
	}
		
	function field_show_spellcheck_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_spellcheck', 'field_show_spellcheck', $prefix);
	}
		
	function field_show_glossary_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_glossary', 'field_show_glossary', $prefix);
	}
		
	function field_show_file_selector_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_file_selector', 'field_show_file_selector', $prefix);
	}
		
	function field_show_formatting_btns_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_formatting_btns', 'field_show_formatting_btns', $prefix);
	}
		
	function field_show_writemode_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_writemode', 'field_show_writemode', $prefix);
	}
	
	// --------------------------------------------------------------------
	
	function _yes_no_row($data, $lang, $data_key, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';
		
		$val_is_y = ($data[$data_key] == 'y') ? TRUE : FALSE;
		
		$this->EE->table->add_row(
			'<strong>'.lang($lang).'</strong>',
				form_radio($prefix.$data_key, 'y', $val_is_y, 'id="'.$data_key.'_y"').NBS.
				lang('yes', $data_key.'_y').NBS.NBS.NBS.NBS.NBS.
				form_radio($prefix.$data_key, 'n', ( ! $val_is_y), 'id="'.$data_key.'_n"').NBS.
				lang('no', $data_key.'_n')
		);
	}
	
}
// END EE_Fieldtype class


/* End of file EE_Fieldtype.php */
/* Location: ./system/expressionengine/fieldtypes/EE_Fieldtype.php */