<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

use EllisLab\ExpressionEngine\Module\Member\Model\Gateway\MemberGateway;

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Utilities extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// Register our menu
		ee()->menu->register_left_nav(array(
			'communicate' => cp_url('utilities/communicate'),
			array(
				'sent' => cp_url('utilities/communicate-sent')
			),
			'cp_translation',
			array(
				// Show installed languages?
				'English (Default)' => cp_url('utilities/communicate')
			),
			'php_info' => cp_url('utilities/php'),
			'import_tools',
			array(
				'file_converter' => cp_url('utilities/import_converter'),
				'member_import' => cp_url('utilities/member_import')
			),
			'sql_manager' => cp_url('utilities/sql'),
			array(
				'query_form' => cp_url('utilities/query')
			),
			'data_operations',
			array(
				'cache_manager' => cp_url('utilities/cache'),
				'statistics' => cp_url('utilities/stats'),
				'search_and_replace' => cp_url('utilities/sandr')
			)
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		// Will redirect based on permissions later
		$this->communicate();
	}

	// --------------------------------------------------------------------

	/**
	 * Communicate
	 *
	 * @access	public
	 * @return	void
	 */
	public function communicate()
	{
		ee()->load->model('addons_model');
		ee()->load->model('communicate_model');

		$default = array(
			'name'			=> '',
			'from'		 	=> ee()->session->userdata('email'),
			'recipient'  	=> '',
			'cc'			=> '',
			'bcc'			=> '',
			'subject' 		=> '',
			'message'		=> '',
			'plaintext_alt'	=> '',
			'priority'		=>  3,
			'text_fmt'		=> 'none',
			'mailtype'		=> $this->config->item('mail_format'),
			'wordwrap'		=> $this->config->item('word_wrap')
		);

		$vars = array(
			'text_formatting_options' => ee()->addons_model->get_plugin_formatting(TRUE)
		);

		$member_groups = array();

		/** -----------------------------
		/**  Fetch form data from cache
		/** -----------------------------*/
		if ($id = $this->input->get('id'))
		{
			$query = $this->communicate_model->get_cached_member_groups($id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$member_groups[] = $row['group_id'];
				}
			}
		}

		// Set up mailing list options
		if ( ! $this->cp->allowed_group('can_email_mailinglist')
			OR ! isset($this->mailinglist_exists)
			OR $this->mailinglist_exists == FALSE)
		{
			$vars['mailing_lists'] = FALSE;
		}
		else
		{
			$query = ee()->communicate_model->get_mailing_lists();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$checked = (ee()->input->post('list_'.$row->list_id) !== FALSE OR in_array($row->list_id, $mailing_lists));
					$vars['mailing_lists'][$row->list_title] = array('name' => 'list_'.$row->list_id, 'value' => $row->list_id, 'checked' => $checked);
				}
			}
			else
			{
				$vars['mailing_lists'] = FALSE;
			}
		}

		// Set up member group emailing options
		if ( ! $this->cp->allowed_group('can_email_member_groups'))
		{
			$vars['member_groups'] = FALSE;
		}
		else
		{
			$addt_where = array('include_in_mailinglists' => 'y');

			$query = $this->member_model->get_member_groups('', $addt_where);

			foreach ($query->result() as $row)
			{
				$checked = ($this->input->post('group_'.$row->group_id) !== FALSE OR in_array($row->group_id, $member_groups));

				$vars['member_groups'][$row->group_title] = array('name' => 'group_'.$row->group_id, 'value' => $row->group_id, 'checked' => $checked);
			}
		}

		ee()->view->cp_page_title = lang('communicate');
		ee()->cp->render('utilities/communicate', $vars + $default);
	}

	// --------------------------------------------------------------------

	/**
	 * PHP Info
	 *
	 * @access	public
	 * @return	void
	 */
	public function php()
	{
		if ( ! $this->cp->allowed_group('can_access_tools'))
		{
			show_error(lang('unauthorized_access'));
		}

		exit(phpinfo());
	}

	// --------------------------------------------------------------------

	/**
	 * Cache Manager
	 *
	 * @access	public
	 * @return	void
	 */
	public function cache()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('cache_type[]', 'lang:caches_to_clear', 'required');

		if (ee()->form_validation->run() !== FALSE)
		{
			// Clear each cache type checked
			foreach (ee()->input->post('cache_type') as $type)
			{
				ee()->functions->clear_caching($type);
			}

			ee()->session->set_flashdata('success', lang('caches_cleared'));
			ee()->functions->redirect(cp_url('utilities/cache'));
		}

		ee()->view->cp_page_title = lang('cache_manager');
		ee()->cp->render('utilities/cache');
	}

	// --------------------------------------------------------------------

	/**
	 * Search and Replace utility
	 *
	 * @access	public
	 * @return	void
	 */
	public function sandr()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'search_term',
				'label' => 'lang:sandr_search_text',
				'rules' => 'required'
			),
			array(
				'field' => 'replace_term',
				'label' => 'lang:sandr_replace_text',
				'rules' => 'required'
			),
			array(
				'field' => 'replace_where',
				'label' => 'lang:sandr_in',
				'rules' => 'required'
			),
			array(
				'field' => 'password_auth',
				'label' => 'lang:current_password',
				'rules' => 'required|auth_password'
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			$replaced = $this->_do_search_and_replace(
				ee()->input->post('search_term'),
				ee()->input->post('replace_term'),
				ee()->input->post('replace_where')
			);

			ee()->session->set_flashdata('success', sprintf(lang('rows_replaced'), (int)$replaced));

			ee()->functions->redirect(cp_url('utilities/sandr'));
		}

		ee()->load->model('tools_model');
		ee()->view->replace_options = $this->tools_model->get_search_replace_options();

		ee()->view->cp_page_title = lang('sandr');
		ee()->cp->render('utilities/sandr');
	}

	// --------------------------------------------------------------------

	/**
	 * Do Search and Replace
	 *
	 * Used by search_and_replace() to execute replacement
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function _do_search_and_replace($search, $replace, $where)
	{
		// escape search and replace for use in queries
		$search = $this->db->escape_str($search);
		$replace = $this->db->escape_str($replace);
		$where = $this->db->escape_str($where);

		if ($where == 'title')
		{
			$sql = "UPDATE `exp_channel_titles` SET `{$where}` = REPLACE(`{$where}`, '{$search}', '{$replace}')";
		}
		elseif ($where == 'preferences' OR strncmp($where, 'site_preferences_', 17) == 0)
		{
			$rows = 0;

			if ($where == 'preferences')
			{
				$site_id = $this->config->item('site_id');
			}
			else
			{
				$site_id = substr($where, strlen('site_preferences_'));
			}

			/** -------------------------------------------
			/**  Site Preferences in Certain Tables/Fields
			/** -------------------------------------------*/

			$preferences = array(
				'exp_channels' => array(
					'channel_title',
					'channel_url',
					'comment_url',
					'channel_description',
					'comment_notify_emails',
					'channel_notify_emails',
					'search_results_url',
					'rss_url'
				),
				'exp_upload_prefs' => array(
					'server_path',
					'properties',
					'file_properties',
					'url'
				),
				'exp_member_groups' => array(
					'group_title',
					'group_description',
					'mbr_delete_notify_emails'
				),
				'exp_global_variables'	=> array('variable_data'),
				'exp_categories'		=> array('cat_image'),
				'exp_forums'			=> array(
					'forum_name',
					'forum_notify_emails',
					'forum_notify_emails_topics'),
				'exp_forum_boards'		=> array(
					'board_label',
					'board_forum_url',
					'board_upload_path',
					'board_notify_emails',
					'board_notify_emails_topics'
				)
			);

			foreach($preferences as $table => $fields)
			{
				if ( ! $this->db->table_exists($table) OR $table == 'exp_forums')
				{
					continue;
				}

				$site_field = ($table == 'exp_forum_boards') ? 'board_site_id' : 'site_id';

				foreach($fields as $field)
				{
					$this->db->query("UPDATE `{$table}`
								SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}')
								WHERE `{$site_field}` = '".$this->db->escape_str($site_id)."'");

					$rows += $this->db->affected_rows();
				}
			}

			if ($this->db->table_exists('exp_forum_boards'))
			{
				$this->db->select('board_id');
				$this->db->where('board_site_id', $site_id);
				$query = $this->db->get('forum_boards');

				if ($query->num_rows() > 0)
				{
					foreach($query->result_array() as $row)
					{
						foreach($preferences['exp_forums'] as $field)
						{
							$this->db->query("UPDATE `exp_forums`
										SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}')
										WHERE `board_id` = '".$this->db->escape_str($row['board_id'])."'");

							$rows += $this->db->affected_rows();
						}
					}
				}
			}

			/** -------------------------------------------
			/**  Site Preferences in Database
			/** -------------------------------------------*/

			$this->config->update_site_prefs(array(), $site_id, $search, $replace);

			$rows += 5;
		}
		elseif ($where == 'template_data')
		{
			$sql = "UPDATE `exp_templates` SET `$where` = REPLACE(`{$where}`, '{$search}', '{$replace}'), `edit_date` = '".$this->localize->now."'";
		}
		elseif (strncmp($where, 'template_', 9) == 0)
		{
			$sql = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{$search}', '{$replace}'), edit_date = '".$this->localize->now."'
					WHERE group_id = '".substr($where,9)."'";
		}
		elseif (strncmp($where, 'field_id_', 9) == 0)
		{
			$sql = "UPDATE `exp_channel_data` SET `{$where}` = REPLACE(`{$where}`, '{$search}', '{$replace}')";
		}
		else
		{
			// no valid $where
			return FALSE;
		}

		if (isset($sql))
		{
			$this->db->query($sql);
			$rows = $this->db->affected_rows();
		}

		return $rows;
	}

	// --------------------------------------------------------------------

	/**
	 * Member import file converter
	 */
	public function import_converter()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('member_import');

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

		if (ee()->form_validation->run() !== FALSE)
		{
			return $this->import_fieldmap();
		}

		ee()->view->cp_page_title = lang('import_converter');
		ee()->cp->render('utilities/import-converter');
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
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
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

		$member_file = ee()->input->post('member_file');
		$enclosure = ee()->input->post('enclosure') ?: '';

		//  Read data file into an array
		$fields = $this->_datafile_to_array($member_file, $delimiter, $enclosure);

		if ( ! isset($fields[0]) OR count($fields[0]) < 3)
		{
			// No point going further if there aren't even the minimum required
			ee()->session->set_flashdata('issue', lang('not_enough_fields'));
			ee()->functions->redirect(cp_url('utilities/import_converter'));
		}

		// Get member table fields
		$this->default_fields = array_values(MemberGateway::getMetaData('field_list'));

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
		//$m_fields = ee()->api->get('MemberField')->order('m_field_name', 'asc')->all();
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
		ee()->cp->set_breadcrumb(cp_url('utilities/import_converter'), lang('import_converter'));
		ee()->cp->render('utilities/import-fieldmap', $vars);
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
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
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
	public function import_fieldmap_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
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

		$member_file = ee()->input->post('member_file');
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
		ee()->cp->set_breadcrumb(cp_url('utilities/import_converter'), lang('import_converter'));
		ee()->cp->render('utilities/import-fieldmap-confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Create XML File
	 *
	 * Creates and XML file from delimited data
	 *
	 * @return	mixed
	 */
	public function import_code_output()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
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

		$member_file = ee()->input->post('member_file');
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
		ee()->cp->set_breadcrumb(cp_url('utilities/import_converter'), lang('import_converter'));
		ee()->cp->render('utilities/import-code-output', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Downloads generated XML from import converter
	 *
	 * @return	void
	 */
	public function download_xml()
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

	// --------------------------------------------------------------------

	/**
	 * Member import
	 */
	public function member_import()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'xml_file',
				 'label'   => 'lang:mbr_xml_file',
				 'rules'   => 'required|file_exists'
			),
			array(
				 'field'   => 'auto_custom_field',
				 'label'   => 'lang:auto_custom_field',
				 'rules'   => ''
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			return $this->member_import_confirm();
		}

		$groups = ee()->api->get('MemberGroup')->order('group_id', 'asc')->all();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		ee()->lang->load('admin');
		ee()->load->model('admin_model');
		$config_fields = ee()->config->prep_view_vars('localization_cfg');
		$date_format = $config_fields['fields']['date_format'];
		$time_format = $config_fields['fields']['time_format'];

		// Restore some values on validation fail
		if (set_value('date_format'))
		{
			$date_format['selected'] = set_value('date_format');
		}

		if (set_value('time_format'))
		{
			$time_format['selected'] = set_value('time_format');
		}

		$vars = array(
			// TODO: Show installed languages
			'language_options' => array('None' => 'None', 'English' => 'English'),
			'member_groups' => $member_groups,
			'date_format' => $date_format,
			'time_format' => $time_format,
			'timezone_menu' => ee()->localize->timezone_menu(set_value('timezones'), 'timezones')
		);

		ee()->view->cp_page_title = lang('member_import');
		ee()->cp->render('utilities/member-import', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Confirm Import Member Data from XML
	 *
	 * Confirmation page for Member Data import
	 *
	 * @return	mixed
	 */
	public function member_import_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->model('member_model');

		$member_group = ee()->api
			->get('MemberGroup')
			->filter('group_id', ee()->input->post('group_id'))
			->first();

		$group_title = '';
		$group_name = ' -- ';

		if ( ! empty($member_group))
		{
			$group_name = $member_group->group_title;
		}

		$data = array(
			'xml_file'   		=> ee()->input->post('xml_file'),
			'group_id' 			=> ee()->input->post('group_id'),
			'language' 			=> (ee()->input->post('language') == lang('none')) ? '' : ee()->input->post('language'),
			'timezones' 		=> ee()->input->post('timezones'),
			'date_format' 		=> ee()->input->post('date_format'),
			'time_format' 		=> ee()->input->post('time_format'),
			'auto_custom_field' => (ee()->input->post('auto_custom_field') == 'y') ? 'y' : 'n'
		);

		ee()->lang->load('admin');
		$localization_cfg = ee()->config->get_config_fields('localization_cfg');
		$added_fields = ee()->input->post('added_fields');

		$vars = array(
			'added_fields'		=> $added_fields,
			'xml_file'   		=> $data['xml_file'],
			'default_group_id'	=> $group_name,
			'language' 			=> ($data['language'] == '') ? lang('none') : ucfirst($data['language']),
			'timezones' 		=> $data['timezones'],
			'date_format' 		=> lang($localization_cfg['date_format'][1][$data['date_format']]),
			'time_format' 		=> lang($localization_cfg['time_format'][1][$data['time_format']]),
			'auto_custom_field' => ($data['auto_custom_field'] == 'y' || ($added_fields && count($added_fields) > 0)) ? lang('yes') : lang('no')
		);

		$map = FALSE;

		if (isset($_POST['field_map']))
		{
			$map = TRUE;
		}

		$vars['form_hidden'] = ($map) ? array_merge($data, $_POST['field_map']) : $data;

		// Branch off here if we need to create a new custom field
		if ($data['auto_custom_field'] == 'y' && ee()->input->post('added_fields') === FALSE)
		{
			$new_custom_fields = $this->_custom_field_check($data['xml_file']);

			if ($new_custom_fields !== FALSE && count($new_custom_fields) > 0)
			{
				return $this->_new_custom_fields_form($vars, $new_custom_fields);
			}

			$vars['message'] = lang('unable_to_parse_custom_fields');
		}

		ee()->view->cp_page_title = lang('confirm_import');
		ee()->cp->set_breadcrumb(cp_url('utilities/member_import'), lang('member_import'));

		ee()->cp->render('utilities/member-import-confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Process XML
	 *
	 * Imports the members from XML and redirects to the index page on successful completion
	 *
	 * @return	void
	 */
	public function process_xml()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('member_import');

		$xml_file   = ( ! $this->input->post('xml_file'))  ? '' : $this->input->post('xml_file');

		//  Read XML file contents
		$this->load->helper('file');
		$contents = read_file($xml_file);

		if ($contents === FALSE)
		{
			ee()->view->form_messages = array('issue' => lang('unable_to_read_file'));
			return $this->view_xml_errors(lang('unable_to_read_file'));
		}

		$this->load->library('xmlparser');

		// parse XML data
		$xml = $this->xmlparser->parse_xml($contents);

		if ($xml === FALSE)
		{
			ee()->view->form_messages = array('issue' => lang('unable_to_parse_xml'));
			return $this->member_import_confirm();
		}

		// Any custom fields exist
		// TODO: MemberField model isn't ready, using query builder
		$this->db->select('m_field_name, m_field_id');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
			$custom_fields = TRUE;

			foreach ($m_custom_fields->result() as $row)
			{
				if (isset($_POST['map'][$row->m_field_name]))
				{
					$this->default_custom_fields[$_POST['map'][$row->m_field_name]] = $row->m_field_id;
				}
				else
				{
					$this->default_custom_fields[$row->m_field_name] = $row->m_field_id;
				}
			}
		}

		$this->validate_xml($xml);

		//  Show Errors
		if (count($this->errors) > 0)
		{
			$out = array();

			foreach($this->errors as $error)
			{
				foreach($error as $val)
				{
					$out[] = $val;
				}
			}

			ee()->view->form_messages = array('issue' => $out);
			return $this->member_import_confirm();
		}

		/** -------------------------------------
		/**  Ok! Cross Fingers and do it!
		/** -------------------------------------*/

		$imports = $this->do_import();

		$msg = lang('import_success_blurb').'<br>'.str_replace('%x', $imports, lang('total_members_imported'));
		$this->session->set_flashdata('success', $msg);

		$this->functions->redirect(cp_url('utilities/member_import'));
	}

	// --------------------------------------------------------------------

	/**
	 * Validate XML for Member Import
	 *
	 * Validates both the format and content of Member Import XML
	 *
	 * @return	mixed
	 */
	public function validate_xml($xml)
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('member_import');
		$this->load->library('validate');

		$this->validate->member_id			= '';
		$this->validate->val_type			= 'new';
		$this->validate->fetch_lang			= TRUE;
		$this->validate->require_cpw		= FALSE;
		$this->validate->enable_log			= FALSE;
		$this->validate->cur_username		= '';
		$this->validate->cur_screen_name	= '';
		$this->validate->cur_password		= '';
		$this->validate->cur_email			= '';

		$i = 0;

		$fields = MemberGateway::getMetaData('field_list');

		foreach ($fields as $field)
		{
			$this->default_fields[$field] = '';
		}

		$this->db->select('m_field_name, m_field_id');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
	 		foreach ($m_custom_fields->result() as $row)
	 		{
				$this->default_custom_fields[$row->m_field_name] = $row->m_field_id;
			}
		}

		// we don't allow <unique_id>
		unset($this->default_fields['unique_id']);

		$u = array(); // username garbage array
		$s = array(); // screen_name garbage array
		$e = array(); // email garbage array
		$m = array(); // member_id garbage array

		if (is_array($xml->children[0]->children))
		{
			foreach($xml->children as $member)
			{
				if ($member->tag == "member")
				{
					foreach($member->children as $tag)
					{
						// Is the XML tag an allowed database field
						if (isset($this->default_fields[$tag->tag]))
						{
							$this->members[$i][$tag->tag] = $tag->value;
						}
						elseif ($tag->tag == 'birthday')
						{
							// We have a special XML format for birthdays that doesn't match the database fields
							foreach($tag->children as $birthday)
							{
									switch ($birthday->tag)
									{
										case 'day':
											$this->members[$i]['bday_d'] = $birthday->value;
											break;
										case 'month':
											$this->members[$i]['bday_m'] = $birthday->value;
											break;
										case 'year':
											$this->members[$i]['bday_y'] = $birthday->value;
											break;
										default:
											$this->errors[] = array(lang('invalid_tag')." '&lt;".$birthday->tag."&gt;'");
											break;
									}
							}


							if ( ! isset($this->members[$i]['bday_d']) || ! isset($this->members[$i]['bday_m']) || ! isset($this->members[$i]['bday_y']))
							{
								$this->errors[] = array(lang('missing_birthday_child'));
							}

							$this->members[$i][$tag->tag] = $tag->value;
						}
						elseif (isset($this->default_custom_fields[$tag->tag]))
						{
							$this->members_custom[$i][$tag->tag] = $tag->value;
						}
						else
						{
							// not a database field and not a <birthday> so club it like a baby seal!
							//$this->errors[] = array(lang('invalid_tag')." '&lt;".$tag->tag."&gt;'");
						}

						/* -------------------------------------
						/*  username, screen_name, and email
						/*  must be validated and unique
						/* -------------------------------------*/

						switch ($tag->tag)
						{
							case 'username':
								$this->validate->username = $tag->value;
								if ( ! in_array($tag->value, $u))
								{
									$u[] = $tag->value;
								}
								else
								{
									$this->errors[] = array(lang('duplicate_username').$tag->value);
								}
								break;
							case 'screen_name':
								$this->validate->screen_name = $tag->value;
								if ( ! in_array($tag->value, $s))
								{
									$s[] = $tag->value;
								}
								else
								{
									$this->errors[] = array(lang('duplicate_screen_name').$tag->value);
								}
								break;
							case 'email':
								if ( ! in_array($tag->value, $e))
								{
									$e[] = $tag->value;
								}
								else
								{
									$this->errors[] = array(lang('duplicate_email').$tag->value);
								}
								$this->validate->email = $tag->value;
								break;
							case 'member_id':
								if ( ! in_array($tag->value, $m))
								{
									$m[] = $tag->value;
								}
								else
								{
									$this->errors[] = array(str_replace("%x", $tag->value, lang('duplicate_member_id')));
								}
								break;
							case 'password':
								// We require a type attribute here, as outlined in the docs.
								// This is a quick error check to ensure its present.
								if ( ! @$tag->attributes['type'])
								{
									show_error(str_replace('%x', $this->validate->username, lang('missing_password_type')));
								}

								// encode password if it is type="text"
								$this->members[$i][$tag->tag] = ($tag->attributes['type'] == 'text') ? sha1($tag->value) : $tag->value;
								break;
						}
					}

					$username 		= (isset($this->members[$i]['username'])) ? $this->members[$i]['username'] : '';
					$screen_name 	= (isset($this->members[$i]['screen_name'])) ? $this->members[$i]['screen_name'] : '';
					$email 			= (isset($this->members[$i]['email'])) ? $this->members[$i]['email'] : '';

					/* -------------------------------------
					/*  Validate separately to display
					/*  exact problem
					/* -------------------------------------*/

					$this->validate->validate_username();

					if ( ! empty($this->validate->errors))
					{
						foreach($this->validate->errors as $key => $val)
						{
							$this->validate->errors[$key] = $val." (Username: '".$username."' - ".lang('within_user_record')." '".$username."')";
						}
						$this->errors[] = $this->validate->errors;
						unset($this->validate->errors);
					}

					$this->validate->validate_screen_name();

					if ( ! empty($this->validate->errors))
					{
						foreach($this->validate->errors as $key => $val)
						{
							$this->validate->errors[$key] = $val." (Screen Name: '".$screen_name."' - ".lang('within_user_record')." '".$username."')";
						}
						$this->errors[] = $this->validate->errors;
						unset($this->validate->errors);
					}

					$this->validate->validate_email();

					if ( ! empty($this->validate->errors))
					{
						foreach($this->validate->errors as $key => $val)
						{
							$this->validate->errors[$key] = $val." (Email: '".$email."' - ".lang('within_user_record')." '".$username."')";
						}
						$this->errors[] = $this->validate->errors;
						unset($this->validate->errors);
					}

					/** -------------------------------------
					/**  Add a random hash if no password is defined
					/** -------------------------------------*/

					if ( ! isset($this->members[$i]['password']))
					{
						$this->members[$i]['password'] = sha1(mt_rand());
					}
					$i++;
				}
				else
				{
					/** -------------------------------------
					/**  Element isn't <member>
					/** -------------------------------------*/

					$this->errors[] = array(lang('invalid_element'));
				}
			}
		}
		else
		{
			/** -------------------------------------
			/**  No children of the root element
			/** -------------------------------------*/

			$this->errors[] = array(lang('invalid_xml'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Do Import
	 *
	 * Inserts new members into the database
	 *
	 * @return	number
	 */
	public function do_import()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		//  Set our optional default values
		$this->default_fields['group_id']			= $this->input->post('group_id');
		$this->default_fields['language']			= ($this->input->post('language') == lang('none') OR $this->input->post('language') == '') ? 'english' : strtolower($this->input->post('language'));
		$this->default_fields['timezone']			= $this->input->post('timezones') ? $this->input->post('timezones') : $this->config->item('default_site_timezone');
		$this->default_fields['date_format']		= $this->input->post('date_format');
		$this->default_fields['time_format']		= $this->input->post('time_format');
		$this->default_fields['include_seconds']	= $this->input->post('include_seconds');
		$this->default_fields['ip_address']			= '0.0.0.0';
		$this->default_fields['join_date']			= $this->localize->now;

		//  Rev it up, no turning back!
		$new_ids = array();
		$counter = 0;
		$custom_fields = (count($this->default_custom_fields) > 0) ? TRUE : FALSE;

		foreach ($this->members as $count => $member)
		{
			$data = array();
			$dupe = FALSE;

			foreach ($this->default_fields as $key => $val)
			{
				if (isset($member[$key]))
				{
					$data[$key] = $member[$key];
				}
				elseif ($val != '')
				{
					$data[$key] = $val;
				}
			}

			if ($custom_fields)
			{
				foreach ($this->default_custom_fields as $name => $id)
				{
					if (isset($this->members_custom[$count][$name]))
					{
						$cdata['m_field_id_'.$id] = $this->members_custom[$count][$name];
					}
				}
			}

			//  Add a unique_id for each member
			$data['unique_id'] = random_string('encrypt');

			/* -------------------------------------
			/*  See if we've already imported a member with this member_id -
			/*  could possibly occur if an auto_increment value is used
			/*  before a specified member_id.
			/* -------------------------------------*/

			if (isset($data['member_id']))
			{
				if (isset($new_ids[$data['member_id']]))
				{
					/* -------------------------------------
					/*  Grab the member so we can re-insert it after we
					/*  take care of this nonsense
					/* -------------------------------------*/
					$dupe = TRUE;

					if ($custom_fields == TRUE)
					{
						$tempcdata = $this->db->get_where('member_data', array('member_id' => $data['member_id']));
					}
				}
			}
			/* -------------------------------------
			/*  Shove it in!
			/*  We are using REPLACE as we want to overwrite existing members if a member id is specified
			/* -------------------------------------*/

			$this->db->replace('members', $data);
			$mid = $this->db->insert_id();

			//  Add the member id to the array of imported member id's
			$new_ids[$mid] = $mid;

			if ($custom_fields == TRUE)
			{
				$cdata['member_id'] = $mid;
			}

			//  Insert the old auto_incremented member, if necessary
			if ($dupe === TRUE)
			{
				unset($tempdata->row['member_id']); // dump the member_id so it can auto_increment a new one
				$this->db->insert('members', $tempdata->row);
				$replace_mid = $this->db->insert_id();

				$new_ids[$replace_mid] = '';

				if ($custom_fields == TRUE)
				{
					$tempcdata->row['member_id'] = $replace_mid;
					$this->db->insert('member_data', $tempcdata->row);
				}
			}

			if ($custom_fields == TRUE)
			{
				$this->db->replace('member_data', $cdata);
			}

			$counter++;
		}

		/** -------------------------------------
		/**  Add records to exp_member_data and exp_member_homepage tables for all imported members
		/** -------------------------------------*/

		$values = '';

		foreach ($new_ids as $key => $val)
		{
			$values .= "('$key'),";
		}

		$values = substr($values, 0, -1);

		if ($custom_fields == FALSE)
		{
			$this->db->query("INSERT INTO exp_member_data (member_id) VALUES ".$values);
		}

		$this->db->query("INSERT INTO exp_member_homepage (member_id) VALUES ".$values);

		//  Update Statistics
		$this->stats->update_member_stats();

		return $counter;
	}

	// --------------------------------------------------------------------

	/**
	 * Custom Field Check
	 *
	 * Finds the fields in the first XML record that do not already exist
	 *
	 * @return	array
	 */
	private function _custom_field_check($xml_file)
	{
		//  Read XML file contents
		$this->load->helper('file');
		$contents = read_file($xml_file);
		$new_custom_fields = array();

		if ($contents === FALSE)
		{
			return;
		}

		$this->load->library('xmlparser');

		// parse XML data
		$xml = $this->xmlparser->parse_xml($contents);

		if ($xml == FALSE)
		{
			return FALSE;
		}

		//  Retreive Valid fields from database
		$query = $this->db->query("SHOW COLUMNS FROM exp_members");
		$existing_fields['birthday'] = '';

		foreach ($query->result_array() as $row)
		{
			$existing_fields[$row['Field']] = '';
		}

		$this->db->select('m_field_name');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
			foreach ($m_custom_fields->result() as $row)
			{
				$existing_c_fields[$row->m_field_name] = '';
			}
		}

		// We go through a single iteration to find the fields
		if (is_array($xml->children[0]->children))
		{
			$member = $xml->children['0'];

			if ($member->tag == "member")
			{
				foreach($member->children as $tag)
				{
					$i = 0;

					// Is the XML tag an allowed database field
					if ( ! isset($existing_fields[$tag->tag]) && ! isset($existing_c_fields[$tag->tag]))
					{
						$new_custom_fields['new'][] = $tag->tag;
						$new_custom_fields['xml_fields'][] = $tag->tag;
					}
					elseif (isset($existing_c_fields[$tag->tag]))
					{
						while($i < 100)
						{
							$i++;

							if ( ! isset($existing_c_fields[$tag->tag.'_'.$i]))
							{
								$new_custom_fields['new'][] = $tag->tag.'_'.$i;
								$new_custom_fields['xml_fields'][] = $tag->tag;
								break;
							}
						}
					}
				}
			}
		}

		return $new_custom_fields;
	}

	// --------------------------------------------------------------------

	/**
	 * New Custom Fields Form
	 *
	 * Generates the form for new custom field settings
	 *
	 * @return	void
	 */
	private function _new_custom_fields_form($vars, $new_custom_fields)
	{
		ee()->javascript->output(array(
			'$(".toggle_all").toggle(
				function(){
					$("input.toggle").each(function() {
						this.checked = true;
					});
				}, function (){
					var checked_status = this.checked;
					$("input.toggle").each(function() {
						this.checked = false;
					});
				}
			);')
		);

		$vars['form_hidden']['new'] = $new_custom_fields['new'];
		$vars['new_fields'] = $new_custom_fields['new'];

		$query = $this->member_model->count_records('member_fields');

		$vars['order_start'] = $query + 1;

		ee()->view->cp_page_title = lang('custom_fields');
		ee()->cp->set_breadcrumb(cp_url('utilities/member_import'), lang('member_import'));
		ee()->cp->render('utilities/member-import-custom', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Create Custom Fields
	 *
	 * Creates the custom field form
	 *
	 * @return	mixed
	 */
	public function create_custom_fields()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_utilities'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->_create_custom_validation();

		if ($this->form_validation->run() === FALSE)
		{
			return $this->member_import_confirm();
		}

		$error = array();
		$taken = array();

		$total_fields = count($this->input->post('create_ids'));

		foreach ($_POST['create_ids'] as $k => $v)
		{
			$data['m_field_name'] 		= $_POST['m_field_name'][$k];
			$data['m_field_label'] 		= $_POST['m_field_label'][$k];
			$data['m_field_description']= (isset($_POST['m_field_description'][$k])) ? $_POST['m_field_description'][$k]	: '';
			$data['m_field_type'] 		= (isset($_POST['m_field_type'][$k])) ? $_POST['m_field_type'][$k] : 'text';
			$data['m_field_list_items'] = (isset($_POST['m_field_list_items'][$k])) ? $_POST['m_field_list_items'][$k] : '';
			$data['m_field_ta_rows'] 	= (isset($_POST['m_field_ta_rows'][$k])) ? $_POST['m_field_ta_rows'][$k] : '100';
			$data['m_field_maxl'] 		= (isset($_POST['m_field_maxl'][$k])) ? $_POST['m_field_maxl'][$k] : '100';
			$data['m_field_width'] 		= (isset($_POST['m_field_width'][$k])) ? $_POST['m_field_width'][$k] : '100%';
			$data['m_field_search'] 	= 'y';
			$data['m_field_required'] 	= (isset($_POST['required'][$k])) ? 'y' : 'n';
			$data['m_field_public'] 	= (isset($_POST['public'][$k])) ? 'y' : 'n';
			$data['m_field_reg'] 		= (isset($_POST['reg_form'][$k])) ? 'y' : 'n';
			$data['m_field_fmt'] 		= (isset($_POST['m_field_fmt'][$k])) ? $_POST['m_field_fmt'][$k] : 'xhtml';
			$data['m_field_order'] 		= (isset($_POST['m_field_order'][$k])) ? $_POST['m_field_order'][$k] : '';

			$this->db->insert('member_fields', $data);
			$field_id = $this->db->insert_id();
			$this->db->query('ALTER table exp_member_data add column m_field_id_'.$field_id.' text NULL DEFAULT NULL');

			$_POST['added_fields'][$_POST['m_field_name'][$k]] = $_POST['m_field_label'][$k];
			//$_POST['xml_custom_fields'][$_POST['xml_field_name'][$k]] = $field_id;

			if ($_POST['new'][$k] != $_POST['m_field_name'][$k])
			{
				$_POST['field_map']['map'][$_POST['m_field_name'][$k]] = $_POST['new'][$k];
			}
			//$this->default_custom_fields[$_POST['m_field_name'][$k]] = 'm_field_id_'.$this->db->insert_id();

		}

		$_POST['auto_custom_field'] = 'n';
		unset($_POST['new']);
		unset($_POST['m_field_name']);
		unset($_POST['m_field_label']);
		unset($_POST['create_ids']);

		return $this->member_import_confirm();
	}

	// --------------------------------------------------------------------

	/**
	 * Create Custom Field Validation
	 *
	 * Validates new custom field submission
	 *
	 * @return	mixed
	 */
	private function _create_custom_validation()
	{
		ee()->load->library('form_validation');

		ee()->invalid_names = $this->cp->invalid_custom_field_names();

		// Gather existing field names
		ee()->db->select('m_field_name');
		$m_custom_fields = $this->db->get('member_fields');

		if ($m_custom_fields->num_rows() > 0)
		{
			foreach ($m_custom_fields->result() as $row)
			{
				$this->taken[] = $row->m_field_name;
			}
		}

		if (isset($_POST['create_ids']))
		{
			foreach($_POST['create_ids'] as $key => $val)
			{
				ee()->form_validation->set_rules("m_field_name[".$key."]", '', 'required|callback__valid_name');
				ee()->form_validation->set_rules("m_field_label[".$key."]", '', 'required');
				ee()->form_validation->set_rules("required[".$key."]", '', '');
				ee()->form_validation->set_rules("public[".$key."]", '', '');
				ee()->form_validation->set_rules("reg_form[".$key."]", '', '');
				ee()->form_validation->set_rules("xml_field_name[".$key."]", '', '');
			}
		}

		ee()->form_validation->set_message('required', lang('s_required'));
	}

	// --------------------------------------------------------------------

	/**
	 * Member import
	 */
	public function query()
	{
		// Super Admins only, please
		if (ee()->session->userdata('group_id') != '1')
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'thequery',
				 'label'   => 'lang:sql_query_to_run',
				 'rules'   => 'required'
			),
			array(
				'field' => 'password_auth',
				'label' => 'lang:current_password',
				'rules' => 'required|auth_password'
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			// Do something...
		}

		ee()->view->cp_page_title = lang('sql_query_form');
		ee()->cp->render('utilities/query');
	}
}

/* End of file ee.php */
/* Location: ./system/expressionengine/controllers/ee.php */