<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class Rte_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Textarea (Rich Text)',
		'version'	=> '1.0'
	);
	
	var $has_array_data = FALSE;
	
	// We consider the editor empty in these cases
	var $_empty = array(
		'',
		'<br>',
		'<br/>',
		'<br />',
		'<p></p>',
		'<p>​</p>' // Zero-width character
	);

	// --------------------------------------------------------------------

	function validate($data)
	{
		if ($this->settings['field_required'] == 'y' && in_array($data, $this->_empty))
		{
			return lang('required');
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{		
		$field = array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
			'rows'	=> $this->settings['field_ta_rows'],
			'dir'	=> $this->settings['field_text_direction']
		);


		// form prepped nonsense
		$data = htmlspecialchars_decode($data, ENT_QUOTES);
		$code_marker = unique_marker('code');
		$code_chunks = array();

		$field_ft = isset($this->settings['field_fmt']) ? $this->settings['field_fmt'] : '';

		if ($field_ft == 'xhtml')
		{
			$data = trim($data);

			// Undo any existing newline formatting. Typography will change
			// it anyways and the rtf will add its own. Having this here
			// prevents growing-newline syndrome in the rtf and lets us switch
			// between rtf and non-rtf.

			$data = preg_replace("/<\/p>\n*<p>/is", "\n\n", $data);
			$data = preg_replace("/<br( \/)?>\n/is", "\n", $data);
		}

		// remove code chunks
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $data, $matches))
		{
			foreach ($matches[1] as $i => $chunk)
			{
				$code_chunks[] = trim($chunk);
				$data = str_replace($matches[0][$i], $code_marker.$i, $data);
			}
		}

		// Check the RTE module and user's preferences
		if ($this->EE->session->userdata('rte_enabled') == 'y' 
			AND $this->EE->config->item('rte_enabled') == 'y')
		{
			$field['class']	= 'WysiHat-field';

			foreach ($code_chunks as &$chunk)
			{
				$chunk = htmlentities($chunk, ENT_QUOTES, 'UTF-8');
				$chunk = str_replace("\n", '<br>', $chunk);
			}

			// xhtml vs br
			if ($this->settings['field_fmt'] == 'xhtml')
			{
				$this->EE->load->library('typography');

				$data = $this->EE->typography->_format_newlines($data);

				// Remove double paragraph tags
				$data = preg_replace("/(<\/?p>)\\1/is", "\\1", $data);
			}
		}

		// put code chunks back
		foreach ($code_chunks as $i => $chunk)
		{
			$data = str_replace($code_marker.$i, '[code]'.$chunk.'[/code]', $data);
		}
		
		// Swap {filedir_x} with the real URL. It will be converted back
		// upon submit by the RTE Image tool.
		$this->EE->load->model('file_upload_preferences_model');
		$dirs = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'));
		
		foreach($dirs as $d)
		{
			// tag to replace
			$filedir = "{filedir_{$d['id']}}";

			$data = str_replace($filedir, $d['url'], $data);
		}
	
		$data = htmlspecialchars($data, ENT_QUOTES);

		$field['value'] = $data;
		
		return form_textarea($field);
	}

	// --------------------------------------------------------------------

	function save($data)
	{
		if ($this->EE->session->userdata('rte_enabled') != 'y' 
			OR $this->EE->config->item('rte_enabled') != 'y')
		{
			return $data;
		}
		
		// If the editor was saved empty, save nothing to database
		// so it behaves as expected with conditional tags
		if (in_array($data, $this->_empty))
		{
			return NULL;
		}

		$data = str_replace('<br>', "\n", $data); // must happen before the decode or we won't know which are ours
		$data = htmlspecialchars_decode($data, ENT_QUOTES);

		// decode double encoded code chunks
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $data, $matches))
		{
			foreach ($matches[1] as $chunk)
			{
				$chunk = trim($chunk);
				$chunk = html_entity_decode($chunk, ENT_QUOTES, 'UTF-8');
				$data = str_replace($matches[0][$i], '[code]'.$chunk.'[/code]', $data);
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = '', $tagdata = '')
	{
		return $data;
	}
	
	// --------------------------------------------------------------------
	
	function display_settings($data)
	{
		$prefix = 'rte';

		// Text direction
		$this->text_direction_row($data, $prefix);

		// Textarea rows
		$field_rows	= ($data['field_ta_rows'] == '') ? 10 : $data['field_ta_rows'];

		$this->EE->table->add_row(
			lang('textarea_rows', $prefix.'_ta_rows'),
			form_input(array(
				'id'	=> $prefix.'_ta_rows',
				'name'	=> $prefix.'_ta_rows',
				'size'	=> 4,
				'value'	=> $field_rows
				)
			)
		);
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		$data['field_type'] = 'rte';
		$data['field_show_fmt'] = 'n';
		$data['field_ta_rows'] = $this->EE->input->post('rte_ta_rows');

		return $data;
	}	
}

// END Rte_ft class

/* End of file ft.rte.php */
/* Location: ./system/expressionengine/modules/ft.rte.php */