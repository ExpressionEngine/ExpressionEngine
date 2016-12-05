<?php

namespace EllisLab\ExpressionEngine\Controller\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Import Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ImportConverter extends Utilities {

	/**
	 * Member import file converter
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_access_import'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('member_import');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'file_location',
					'desc' => 'file_location_desc',
					'fields' => array(
						'member_file' => array(
							'type' => 'text',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'delimiting_char',
					'desc' => 'delimiting_char_desc',
					'fields' => array(
						'delimiter' => array(
							'type' => 'radio',
							'choices' => array(
								'comma' => lang('comma_delimit') . ' <i>,</i>',
								'tab' => lang('tab_delimit'),
								'pipe' => lang('pipe_delimit') . ' <i>|</i>',
								'other' => lang('other_delimit')
							),
							'value' => 'comma'
						),
						'delimiter_special' => array('type' => 'text')
					)
				),
				array(
					'title' => 'enclosing_char',
					'desc' => 'enclosing_char_desc',
					'fields' => array(
						'enclosure' => array('type' => 'text')
					)
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'member_file',
				 'label'   => 'lang:file_location',
				 'rules'   => 'required|file_exists'
			),
			array(
				 'field'   => 'delimiter',
				 'label'   => 'lang:delimiting_char',
				 'rules'   => 'required|enum[tab,other,comma,pipe]'
			),
			array(
				 'field'   => 'delimiter_special',
				 'label'   => 'lang:delimiting_char',
				 'rules'   => 'trim|callback__not_alphanu'
			),
			array(
				 'field'   => 'enclosure',
				 'label'   => 'lang:enclosing_char',
				 'rules'   => 'callback__prep_enclosure'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			return $this->import_fieldmap();
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('file_not_converted'), lang('file_not_converted_desc'));
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('import_converter');
		ee()->view->base_url = ee('CP/URL')->make('utilities/import-converter');
		ee()->view->save_btn_text = 'import_convert_btn';
		ee()->view->save_btn_text_working = 'import_convert_btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Not Alpha or Numeric
	 *
	 * Validation callback that makes sure that no alphanumeric chars are submitted
	 *
	 * @param	string
	 * @return	boolean
	 */
	public function _not_alphanu($str = '')
	{
		if (ee()->input->post('delimiter') == 'other')
		{
			if ($str == '')
			{
				ee()->form_validation->set_message('_not_alphanu', str_replace('%x', lang('other'), lang('no_delimiter')));
				return FALSE;
			}

			preg_match("/[\w\d]*/", $str, $matches);

			if ($matches[0] != '')
			{
				ee()->form_validation->set_message('_not_alphanu', lang('alphanumeric_not_allowed'));
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Enclosure
	 *
	 * Undo changes made by form prep
	 *
	 * @return	string
	 */
	public function _prep_enclosure($enclosure)
	{
		// undo changes made by form prep as we need the literal characters
		// and htmlspecialchars_decode() doesn't exist until PHP 5, so...
		$enclosure = str_replace('&#39;', "'", $enclosure);
		$enclosure = str_replace('&amp;', "&", $enclosure);
		$enclosure = str_replace('&lt;', "<", $enclosure);
		$enclosure = str_replace('&gt;', ">", $enclosure);
		$enclosure = str_replace('&quot;', '"', $enclosure);
		$enclosure = stripslashes($enclosure);

		return $enclosure;
	}

	// --------------------------------------------------------------------

	/**
	 * For mapping to existing member fields
	 */
	public function import_fieldmap()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		//  Snag form POST data
		switch (ee()->input->post('delimiter'))
		{
			case 'tab'	:	$delimiter = "\t";
				break;
			case 'pipe'	:	$delimiter = "|";
				break;
			case 'other':	$delimiter = ee()->input->post('delimiter_special');
				break;
			case 'comma':
			default:		$delimiter = ",";
		}

		$member_file = parse_config_variables(ee()->input->post('member_file'));
		$enclosure = ee()->input->post('enclosure') ?: '';

		//  Read data file into an array
		$fields = $this->_datafile_to_array($member_file, $delimiter, $enclosure);

		if ( ! isset($fields[0]) OR count($fields[0]) < 3)
		{
			// No point going further if there aren't even the minimum required
			ee()->view->set_message('issue', lang('not_enough_fields'), lang('not_enough_fields_desc'), TRUE);
			ee()->functions->redirect(ee('CP/URL')->make('utilities/import_converter'));
		}

		// Get member table fields
		$this->default_fields = ee('Model')->make('Member')->getFields();

		ksort($this->default_fields);
		$vars['select_options'][''] = lang('select');

		foreach ($this->default_fields as $key => $val)
		{
			$vars['select_options'][$val] = $val;
		}

		// we do not allow <unique_id> or <member_id> in our XML format
		unset($vars['select_options']['unique_id']);
		unset($vars['select_options']['member_id']);

		// When MemberField model is ready
		//$m_fields = ee('Model')->get('MemberField')->order('m_field_name', 'asc')->all();
		$m_fields = ee()->db->order_by('m_field_name', 'asc')->get('member_fields');

		if ($m_fields->num_rows() > 0)
		{
			foreach ($m_fields->result() as $field)
			{
				$vars['select_options'][$field->m_field_name] = $field->m_field_name;
			}
		}

		$vars['fields'] = $fields;

		$vars['form_hidden'] = array(
			'member_file'		=> ee()->input->post('member_file'),
			'delimiter'			=> ee()->input->post('delimiter'),
			'enclosure'			=> $enclosure,
			'delimiter_special'	=> $delimiter
		);

		$vars['encrypt'] = '';

		ee()->view->cp_page_title = lang('import_converter') . ' - ' . lang('assign_fields');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/import_converter'), lang('import_converter'));
		ee()->cp->render('utilities/import/fieldmap', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Datafile to Array
	 *
	 * Read delimited data file into an array
	 *
	 * @return	array
	 */
	private function _datafile_to_array($file, $delimiter, $enclosure)
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$contents = file($file);
		$fields = array();

		//  Parse file into array
		if ($enclosure == '')
		{
			foreach ($contents as $line)
			{
				$fields[] = explode($delimiter, $line);
			}
		}
		else
		{
			foreach ($contents as $line)
			{
				preg_match_all("/".preg_quote($enclosure)."(.*?)".preg_quote($enclosure)."/si", $line, $matches);
				$fields[] = $matches[1];
			}
		}

		return $fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Pair Fields Form
	 *
	 * For mapping to existing custom fields
	 *
	 * @return	void
	 */
	public function importFieldmapConfirm()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$paired = array();

		// Validate selected fields
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'field')
			{
				$_POST['unique_check'][$key] = $val;
				$paired[$key] = $val;
			}
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'unique_check',
				 'label'   => 'lang:other',
				 'rules'   => 'callback__unique_required'
			),
			array(
				 'field'   => 'encrypt',
				 'label'   => 'lang:plain_text_passwords',
				 'rules'   => 'required'
			)
		));

		if (ee()->form_validation->run() === FALSE)
		{
			return $this->import_fieldmap();
		}

		//  Snag form POST data
		switch (ee()->input->post('delimiter'))
		{
			case 'tab'	:	$delimiter = "\t";
				break;
			case 'pipe'	:	$delimiter = "|";
				break;
			case 'other':	$delimiter = ee()->input->post('delimiter_special');
				break;
			case 'comma':
			default:		$delimiter = ",";
		}

		$member_file = parse_config_variables(ee()->input->post('member_file'));
		$enclosure = ee()->input->post('enclosure') ?: '';

		//  Read data file into an array
		$fields = $this->_datafile_to_array($member_file, $delimiter, $enclosure);

		$vars['fields'] = $fields;
		$vars['paired'] = $paired;

		$vars['form_hidden'] = array(
			'member_file'		=> $member_file,
			'delimiter'			=> ee()->input->post('delimiter'),
			'enclosure'			=> $enclosure,
			'delimiter_special'	=> $delimiter,
			'encrypt'			=> ee()->input->post('encrypt')
		);

		$vars['form_hidden'] = array_merge($vars['form_hidden'], $paired);

		ee()->view->cp_page_title = lang('confirm_assignments');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/import_converter'), lang('import_converter'));
		ee()->cp->render('utilities/import/fieldmap-confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Create XML File
	 *
	 * Creates and XML file from delimited data
	 *
	 * @return	mixed
	 */
	public function importCodeOutput()
	{
		if ( ! ee()->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		//  Snag form POST data
		switch (ee()->input->post('delimiter'))
		{
			case 'tab'	:	$delimiter = "\t";
				break;
			case 'pipe'	:	$delimiter = "|";
				break;
			case 'other':	$delimiter = ee()->input->post('delimiter_special');
				break;
			case 'comma':
			default:		$delimiter = ",";
		}

		$member_file = parse_config_variables(ee()->input->post('member_file'));
		$enclosure = ee()->input->post('enclosure') ?: '';
		$encrypt = ($this->input->post('encrypt') == 'y');

		ee()->load->helper(array('file', 'xml'));

		//  Read file contents
		$contents = read_file($member_file);

		//  Get structure
		$structure = array();

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == 'field')
			{
				$structure[] = $val;
			}
		}

		ee()->load->library('xmlparser');

		// parse XML data
		$xml = ee()->xmlparser->parse_xml($contents);

		$params = array(
			'data'			=> $contents,
			'structure'		=> $structure,
			'root'			=> 'members',
			'element'		=> 'member',
			'delimiter'		=> $delimiter,
			'enclosure'		=> $enclosure
		);

		$xml = ee()->xmlparser->delimited_to_xml($params, 1);

		//  Add type="text" parameter for plaintext passwords
		if ($encrypt === TRUE)
		{
			$xml = str_replace('<password>', '<password type="text">', $xml);
		}

		if ( ! empty(ee()->xmlparser->errors))
		{
			return show_error($this->xmlparser->errors);
		}

		$vars['code'] = $xml;
		$vars['generated'] = ee()->localize->human_time();
		$vars['username'] = ee()->session->userdata('username');

		ee()->view->cp_page_title = lang('xml_code');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/import_converter'), lang('import_converter'));
		ee()->cp->render('utilities/import/code-output', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Downloads generated XML from import converter
	 *
	 * @return	void
	 */
	public function downloadXml()
	{
		ee()->load->helper('download');
		force_download(
			'member_'.ee()->localize->format_date('%y%m%d').'.xml',
			ee()->input->post('xml')
		);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Unique Required
	 *
	 * Check for uniqueness and required values
	 *
	 * @return	void
	 */
	public function _unique_required($selected_fields)
	{
		//  Get field pairings
		$paired = array();
		$mssg = array();

		if (is_array($selected_fields))
		{
			foreach ($selected_fields as $val)
			{
				if ($val != '' && in_array($val, $paired))
				{
					$mssg[] = str_replace("%x", $val, lang('duplicate_field_assignment'));
				}

				$paired[] = $val;
			}
		}

		if ( ! in_array('username', $paired))
		{
			$mssg[] = lang('missing_username_field');
		}

		if ( ! in_array('screen_name', $paired))
		{
			$mssg[] = lang('missing_screen_name_field');
		}

		if ( ! in_array('email', $paired))
		{
			$mssg[] = lang('missing_email_field');
		}

		if (count($mssg) > 0)
		{
			$out = implode('<br>', $mssg);
			$this->form_validation->set_message('_unique_required', $out);
			return FALSE;
		}

		return TRUE;
	}

}
// END CLASS

// EOF
