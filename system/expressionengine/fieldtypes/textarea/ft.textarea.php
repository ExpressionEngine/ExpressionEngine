<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Textarea Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Textarea_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Textarea',
		'version'	=> '1.0'
	);
	
	var $has_array_data = FALSE;

	// --------------------------------------------------------------------

	function validate($data)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{		
		$field = array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
		//	'value'	=> $data, // set below
			'rows'	=> $this->settings['field_ta_rows'],
			'dir'	=> $this->settings['field_text_direction']
		);


		// form prepped nonsense
		$data = htmlspecialchars_decode($data);
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

		// RTE?
		if ( ! isset($this->settings['field_enable_rte']))
		{
			$this->settings['field_enable_rte'] = 'n';
		}

		if ($this->settings['field_enable_rte'] == 'y')
		{
			$field['class']	= 'rte';

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
		
		$data = htmlspecialchars($data);

		$field['value'] = $data;
		return form_textarea($field);
	}

	// --------------------------------------------------------------------

	function save($data)
	{
		if ( ! isset($this->settings['field_enable_rte']) OR
			$this->settings['field_enable_rte'] == 'n')
		{
			return $data;
		}

		$data = str_replace('<br>', "\n", $data); // must happen before the decode or we won't know which are ours
		$data = htmlspecialchars_decode($data);

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
		$prefix = 'textarea';
		
		// RTE setup
		$this->EE->load->model('addons_model');
		if ( ! isset($data['field_enable_rte']))
		{
			$data['field_enable_rte'] = 'n';
		}
		
		$field_rows	= ($data['field_ta_rows'] == '') ? 6 : $data['field_ta_rows'];
		
		$this->EE->table->add_row(
			lang('textarea_rows', 'field_ta_rows'),
			form_input(array('id'=>'field_ta_rows','name'=>'field_ta_rows', 'size'=>4,'value'=>$field_rows))
		);
		
		$this->field_formatting_row($data, $prefix);
		if ($this->EE->addons_model->module_installed('rte') &&
		 	$this->EE->config->item('rte_enabled') == 'y')
		{
			$this->EE->lang->loadfile('rte');
			
			# RTE-related JavaScript
			$this->EE->javascript->output('
				// RTE adjustments
				// Can’t have RTE alongside Formatting Buttons, Smileys or WriteMode
				var
				$dependent	= $("[name=textarea_field_enable_rte]," +
								"[name=textarea_field_show_formatting_btns]," +
								"[name=textarea_field_show_smileys]," +
								"[name=textarea_field_show_writemode]," +
								"[name=textarea_field_show_fmt]," +
								"[name=textarea_field_show_glossary]," +
								"[name=textarea_field_show_file_selector]")
									.change(function(){
										var
										$this	= $(this),
										name	= $this.attr("name"),
										value	= $("[name=" + name + "]:checked").val();
										if ( name == "textarea_field_enable_rte" &&
										 	 value == "y" )
										{
											$dependent
												.not("[name=" + name + "]")
												.filter("[value=n]")
												.attr("checked",true);
										}
										else if ( name != "textarea_field_enable_rte" &&
										 	 	  value == "y" )
										{
											$("[name=textarea_field_enable_rte][value=n]")
												.attr("checked",true);
										}
									 });
			');
			$this->EE->javascript->compile();
						
			$this->_yes_no_row($data, 'enable_rte_for_field', 'field_enable_rte', $prefix);
		}
		$this->text_direction_row($data, $prefix);
		$this->field_show_formatting_btns_row($data, $prefix);
		$this->field_show_smileys_row($data, $prefix);
		$this->field_show_glossary_row($data, $prefix);
		$this->field_show_spellcheck_row($data, $prefix);
		$this->field_show_writemode_row($data, $prefix);
		$this->field_show_file_selector_row($data, $prefix);
	}
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		return array(
			'field_enable_rte'	=> $this->EE->input->post('textarea_field_enable_rte')
		);
	}	
}

// END Textarea_ft class

/* End of file ft.textarea.php */
/* Location: ./system/expressionengine/fieldtypes/ft.textarea.php */