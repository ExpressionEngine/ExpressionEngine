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

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Admin_system extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		$this->_restrict_prefs_access();
		$this->lang->loadfile('homepage');
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		$this->_restrict_prefs_access();

		$this->view->controller = 'admin';
		$this->view->cp_page_title = lang('admin_system');

		$this->cp->render('_shared/overview');
	}

	// --------------------------------------------------------------------

	/**
	 * Email Configuration
	 *
	 * @access	public
	 * @return	void
	 */
	function email_configuration()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('email_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * General Configuration
	 *
	 * @access	public
	 * @return	void
	 */
	function general_configuration()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('general_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Software Registration
	 *
	 * @access	public
	 * @return	void
	 */
	function software_registration()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('software_registration', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Config Manager
	 *
	 * Used to display the various preference pages
	 *
	 * @access	public
	 * @return	void
	 */
	function _config_manager($type, $return_loc)
	{
		$this->_restrict_prefs_access();

		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"],
			headers: {
				1: { sorter: false }
			},
			textExtraction: function(node) {
				var c = $(node).children();

				if (c.length) {
					return c.text();
				}
				else {
					return node.innerHTML;
				}
			}
		}');

		$this->view->cp_page_title = lang($type);

		$this->load->library('table');
		$this->load->library('form_validation');
		$this->load->model('admin_model');

		$config_pages = array('general_cfg', 'cp_cfg', 'channel_cfg',
			'member_cfg', 'output_cfg', 'debug_cfg', 'db_cfg', 'security_cfg',
			'throttling_cfg', 'localization_cfg', 'email_cfg', 'cookie_cfg',
			'image_cfg', 'captcha_cfg', 'template_cfg', 'censoring_cfg',
			'mailinglist_cfg', 'emoticon_cfg', 'tracking_cfg', 'avatar_cfg',
			'search_log_cfg', 'recount_prefs', 'software_registration'
		);
		if ( ! in_array($type, $config_pages))
		{
			show_error(lang('unauthorized_access'));
		}

		if (count($_POST))
		{
			$this->load->helper('html');

			// Grab the field definitions for the settings of this type
			$field_defs = ee()->config->get_config_fields($type);

			// Set validation rules
			$rules = array();

			foreach($_POST as $key => $val)
			{
				$rules[] = array(
					'field' => $key,
					'label' => '<strong>'.lang($key).'</strong>',
					'rules' => (isset($field_defs[$key][2])) ? $field_defs[$key][2] : ''
				);
			}

			// Validate
			$this->form_validation->set_rules($rules);
			$validated = $this->form_validation->run();

			$vars = ee()->config->prep_view_vars($type);
			$vars['form_action'] = 'C=admin_system'.AMP.'M='.$return_loc;

			if ($validated)
			{
				$config_update = $this->config->update_site_prefs($_POST);

				if ( ! empty($config_update))
				{
					$this->session->set_flashdata('message_failure', ul($config_update, array('class' => 'bad_path_error_list')));
				}
				else
				{
					$this->session->set_flashdata('message_success', lang('preferences_updated'));
				}

				$this->functions->redirect(BASE.AMP.'C=admin_system'.AMP.'M='.$return_loc);
			}
			else
			{
				$vars['cp_messages']['error'] = $this->form_validation->error_string('', '');

				$this->cp->render('admin/config_pages', $vars);

				return;
			}
		}


		// First view
		$vars = ee()->config->prep_view_vars($type);
		$vars['form_action'] = 'C=admin_system'.AMP.'M='.$return_loc;

		$vars['cp_notice'] = FALSE;
		$vars['info_message_open'] = ($this->input->cookie('home_msg_state') != 'closed');

		// Check to see if there are any items in the developer log
		ee()->load->model('tools_model');
		$unviewed_developer_logs = ee()->tools_model->count_unviewed_developer_logs();

		if ($unviewed_developer_logs > 0)
		{
			$vars['cp_notice'] = sprintf(
				lang('developer_logs'),
				$unviewed_developer_logs,
				BASE.AMP.'C=tools_logs'.AMP.'M=view_developer_log'
			);

			ee()->javascript->set_global('importantMessage.state', $vars['info_message_open']);
		}


		$this->cp->render('admin/config_pages', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * A validation callback for required email configuration strings only
	 * if SMTP is the selected protocol method
	 *
	 * @access	public
	 * @param	string	$str	the string being validated
	 * @return	boolean	Whether or not the string passed validation
	 **/
	public function _smtp_required_field($str)
	{
		if ($this->input->post('mail_protocol') == 'smtp' && trim($str) == '')
		{
			$this->form_validation->set_message('_smtp_required_field', lang('empty_stmp_fields'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Validates format of submitted license number
	 *
	 * @return vool
	 **/
	public function _valid_license_pattern($license)
	{
		$valid_pattern = valid_license_pattern($license);

		if ( ! $valid_pattern)
		{
			$this->form_validation->set_message('_valid_license_pattern', lang('invalid_license_number'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Control Panel Settings
	 *
	 * @access	public
	 * @return	void
	 */
	function control_panel_settings()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('cp_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Security and Session Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function security_session_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('security_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Output and Debugging Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function output_debugging_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('output_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Localization Settings
	 *
	 * @access	public
	 * @return	void
	 */
	function localization_settings()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('localization_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Database Settings
	 *
	 * @access	public
	 * @return	void
	 */
	function database_settings()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('db_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Recount Preferences
	 *
	 * @access public
	 * @return void
	 */
	function recount_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('recount_prefs', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Mailing List Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function mailing_list_preferences()
	{
		// this page is only linked to from the mailinglist module
		// change the breadcrumb for better navigation

		$modules = $this->cp->get_installed_modules();

		if (isset($modules['mailinglist']))
		{
			$this->lang->loadfile('mailinglist');
			$this->view->cp_breadcrumbs = array(
				BASE.AMP.'C=addons_modules' => lang('nav_modules'),
				BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist' => lang('mailinglist_module_name')
			);
		}


		$this->_restrict_prefs_access();
		$this->_config_manager('mailinglist_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Image Resizing Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function image_resizing_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('image_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * CAPTCHA Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function captcha_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('captcha_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Referrer Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function tracking_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('tracking_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Cookie Settings
	 *
	 * @access	public
	 * @return	void
	 */
	function cookie_settings()
	{
		$this->_restrict_prefs_access();

		$this->lang->loadfile('email');
		$this->_config_manager('cookie_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Search Term Log Configuration
	 *
	 * @access	public
	 * @return	void
	 */
	function search_log_configuration()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('search_log_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Throttling Configuration
	 *
	 * @access	public
	 * @return	void
	 */
	function throttling_configuration()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('throttling_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Word Censoring
	 *
	 * @access	public
	 * @return	void
	 */
	function word_censoring()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('censoring_cfg', __FUNCTION__);
	}

	// --------------------------------------------------------------------

	/**
	 * Emoticon Preferences
	 *
	 * @access	public
	 * @return	void
	 */
	function emoticon_preferences()
	{
		$this->_restrict_prefs_access();
		$this->_config_manager('emoticon_cfg', __FUNCTION__);
	}


	// --------------------------------------------------------------------

	/**
	 * Configuration Editor
	 *
	 * This interface allows for the editing of config.php items through the CP
	 *
	 * @access	public
	 * @return	void
	 */
	function config_editor()
	{
		$this->_restrict_prefs_access();

		$this->view->cp_page_title = lang('config_editor');

		$vars['config_items'] = $this->config->default_ini;
		ksort($vars['config_items']);

		// There are some config keys that we don't want to allow through here, let's go though and unset them
		$blacklist_items = array(
			'app_version',
			'base_url', // doesn't really do anything in EE, removed
			'subclass_prefix',
			'enable_query_strings',
			'directory_trigger',
			'controller_trigger',
			'function_trigger'
		);

		$vars['hidden'] = array();

		foreach ($blacklist_items as $blacklist_item)
		{
			if (isset($vars['config_items'][$blacklist_item]))
			{
				$vars['hidden'][$blacklist_item] = $vars['config_items'][$blacklist_item];
				unset($vars['config_items'][$blacklist_item]);
			}
		}

		$this->javascript->output('
			$("table tbody tr:visible:even").addClass("even");
			$("table tbody tr:visible:odd").addClass("odd");
		');

		$this->cp->render('admin/config_editor', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * CodeIgniter Configuration Process
	 *
	 * Processes and saves the changes from config_editor()
	 *
	 * @access	public
	 * @return	void
	 */
	function config_editor_process()
	{
		$this->_restrict_prefs_access();

		$this->load->helper('security');

		// new config added?
		if ($this->input->post('config_name') != '')
		{
			$_POST[url_title($_POST['config_name'])] = $_POST['config_setting'];
		}

		unset($_POST['config_name'], $_POST['config_setting'], $_POST['update']); // Submit button

		$config = xss_clean($_POST);

		$this->config->_update_config($config);

		$this->session->set_flashdata('message_success', lang('preferences_updated'));
		$this->functions->redirect(BASE.AMP.'C=admin_system'.AMP.'M=config_editor');
	}

	// --------------------------------------------------------------------

	/**
	 * Restrict Access
	 *
	 * Helper function for the most common access level in this class
	 *
	 * @access	private
	 * @return	void
	 */
	private function _restrict_prefs_access()
	{
		if ( ! $this->cp->allowed_group('can_access_admin', 'can_access_sys_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}
	}


}

/* End of file admin_system.php */
/* Location: ./system/expressionengine/controllers/cp/admin_system.php */
