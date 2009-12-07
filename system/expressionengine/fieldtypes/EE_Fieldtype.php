<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
	
	function display_publish_field($data)
	{
		$vars['glossary_items'] = $this->EE->load->view('content/_assets/glossary_items', '', TRUE);
		$this->EE->load->vars($vars);
	
		return $this->display_field($data);
	}
	
	// --------------------------------------------------------------------
	
	function field_formatting_row($data, $prefix = FALSE)
	{
		// @todo
		$edit_format_link = '';
		$prefix = ($prefix) ? $prefix.'_' : '';

		$this->EE->table->add_row(
			lang('deft_field_formatting', $prefix.'field_fmt'),
			form_dropdown($prefix.'field_fmt', $data['field_fmt_options'], $data['field_fmt'], 'id="'.$prefix.'field_fmt"').
				$edit_format_link.BR.BR.
				'<strong>'.lang('show_formatting_buttons').'</strong>'.BR.
				form_radio($prefix.'field_show_fmt', 'y', $data['field_show_fmt_y'], 'id="'.$prefix.'field_show_fmt_y"').
				lang('yes', 'field_show_fmt_y').NBS.NBS.NBS.NBS.NBS.
				form_radio($prefix.'field_show_fmt', 'n', $data['field_show_fmt_n'], 'id="'.$prefix.'field_show_fmt_n"').
				lang('no', 'field_show_fmt_n')
		);
	}
	
	// --------------------------------------------------------------------
	
	function text_direction_row($data, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		$this->EE->table->add_row(
			'<strong>'.lang('text_direction').'</strong>',
			form_radio($prefix.'field_text_direction', 'ltr', $data['field_text_direction_ltr'], 'id="field_text_direction_ltr"').
				lang('ltr', 'field_text_direction_ltr').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
				form_radio($prefix.'field_text_direction', 'rtl', $data['field_text_direction_rtl'], 'id="field_text_direction_rtl"').
				lang('rtl', 'field_text_direction_rtl')
		);		
	}
	
	function field_content_type_row($data, $prefix = FALSE)
	{
		$suf = $prefix;
		$prefix = ($prefix) ? $prefix.'_' : '';
		
		$this->EE->table->add_row(
			lang('field_content_'.$suf, 'field_content_'.$suf),
			form_dropdown($prefix.'field_content_type', $data['field_content_options_'.$suf], $data['field_content_'.$suf], 'id="'.$prefix.'field_content_type"')
		);				
	}

	// --------------------------------------------------------------------
	
	function multi_item_row($data)
	{
		$this->EE->table->add_row(
			'@todo option toggle things',
			'<p class="field_format_option select_format_n">'.
				lang('field_list_items', 'select_list_items').
				lang('multi_list_items', 'multi_select_list_items').BR.
				lang('field_list_instructions').BR.
				form_textarea(array('id'=>'field_list_items','name'=>'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>$field_list_items)).
			'</p>
			<p class="field_format_option select_format_y">'.
				lang('select_channel_for_field', 'field_pre_populate_id').
				form_dropdown('field_pre_populate_id', $field_pre_populate_id_options, $field_pre_populate_id_select, 'id="field_pre_populate_id"').
			'</p>'
		);
	}
	
	// --------------------------------------------------------------------
	
	function display_settings($data)
	{
		return '';
	}
	
	// --------------------------------------------------------------------
	
	function save($data)
	{
		return $data;
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
}

// END EE_Fieldtype class


/* End of file EE_Fieldtype.php */
/* Location: ./system/expressionengine/fieldtypes/EE_Fieldtype.php */