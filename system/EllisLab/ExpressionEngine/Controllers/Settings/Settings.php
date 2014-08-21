<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Settings extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_admin', 'can_access_sys_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('settings');
		ee()->load->library('form_validation');

		// Register our menu
		ee()->menu->register_left_nav(array(
			'general_settings' => cp_url('settings/general'),
			array(
				'license_and_reg' => cp_url('settings/license'),
				'url_path_settings' => cp_url('settings/urls'),
				'outgoing_email' => cp_url('settings/email'),
				'debugging_output' => cp_url('settings/debug-output')
			),
			'content_and_design' => cp_url('settings/content-design'),
			array(
				'comment_settings' => cp_url('settings/comments'),
				'template_settings' => cp_url('settings/template'),
				'upload_directories' => cp_url('settings/uploads'),
				'word_censoring' => cp_url('settings/word-censor')
			),
			'members' => cp_url('settings/members'),
			array(
				'messages' => cp_url('settings/messages'),
				'avatars' => cp_url('settings/avatars')
			),
			'security_privacy' => cp_url('settings/security-privacy'),
			array(
				'access_throttling' => cp_url('settings/throttling'),
				'captcha' => cp_url('settings/captcha')
			),
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Index
	 */
	public function index()
	{
		ee()->functions->redirect(cp_url('settings/general'));
	}

	// --------------------------------------------------------------------

	/**
	 * Generic method to take an array of fields structured for the form
	 * view, check POST for their values, and then save the values in site
	 * preferences
	 *
	 * @param	array	$sections	Array of sections passed to form view
	 * @return	bool	Success or failure of saving the settings
	 */
	protected function saveSettings($sections)
	{
		$fields = array();

		// Make sure we're getting only the fields we asked for
		foreach ($sections as $settings)
		{
			foreach ($settings as $setting)
			{
				foreach ($setting['fields'] as $field_name => $field)
				{
					$fields[$field_name] = ee()->input->post($field_name);
				}
			}
		}

		$config_update = ee()->config->update_site_prefs($fields);

		if ( ! empty($config_update))
		{
			ee()->view->set_message('issue', lang('cp_message_issue'), implode('<br>', $config_update), TRUE);
			
			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

/* End of file Settings.php */
/* Location: ./system/expressionengine/controllers/cp/Settings/Settings.php */