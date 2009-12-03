<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Encode multi select field data
 *
 * Creates a pipe concatenated string with all superfluous pipes escaped
 *
 * @access	public
 * @param	array	the multi select data
 * @return	string
 */
function encode_multi_field($data = array())
{
	if ( ! is_array($data))
	{
		$data = array($data);
	}
	
	// Escape pipes
	foreach($data as $key => $val)
	{
		$data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), $val);
	}
	
	// Implode on seperator
	return implode('|', $data);
}

// ------------------------------------------------------------------------

/**
 * Decode multi select field data
 *
 * Explodes the stored string and cleans up escapes
 *
 * @access	public
 * @param	string	data string
 * @return	array
 */
function decode_multi_field($data = '')
{
	if ($data == '')
	{
		return array();
	}
	
	if (is_array($data))
	{
		return $data;
	}
	
	// Explode at non-escaped pipes ([\\\\] == one backslash, thanks to php + regex escaping)
	$data = preg_split("#(?<![\\\\])[|]#", $data);
	
	// Reduce slashes
	return str_replace(array('\|', '\\\\'), array('|', '\\'), $data);
}

function create_multi_select_thing($data)
{
	static $created = FALSE;
	
	if ( ! $created)
	{
		$EE =& get_instance();
		$EE->table->add_row(
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
		
		$created = TRUE;
	}
}

/* End of file custom_field_helper.php */
/* Location: ./system/expressionengine/helpers/custom_field_helper.php */