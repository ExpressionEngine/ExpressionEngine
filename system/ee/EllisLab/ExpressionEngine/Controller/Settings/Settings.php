<?php

namespace EllisLab\ExpressionEngine\Controller\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
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
		ee()->load->model('addons_model');

		$this->generateSidebar();

		ee()->view->header = array(
			'title' => lang('system_settings'),
		);
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$list = $sidebar->addHeader(lang('general_settings'), ee('CP/URL', 'settings/general'))
			->addBasicList();

		$list->addItem(lang('license_and_reg'), ee('CP/URL', 'settings/license'));
		$list->addItem(lang('url_path_settings'), ee('CP/URL', 'settings/urls'));
		$list->addItem(lang('outgoing_email'), ee('CP/URL', 'settings/email'));
		$list->addItem(lang('debugging_output'), ee('CP/URL', 'settings/debug-output'));

		$list = $sidebar->addHeader(lang('content_and_design'), ee('CP/URL', 'settings/content-design'))
			->addBasicList();

		$list->addItem(lang('comment_settings'), ee('CP/URL', 'settings/comments'));
		$list->addItem(lang('html_buttons'), ee('CP/URL', 'settings/buttons'));
		$list->addItem(lang('template_settings'), ee('CP/URL', 'settings/template'));

		if (ee()->addons_model->module_installed('pages'))
		{
			$list->addItem(lang('pages_settings'), ee('CP/URL', 'addons/settings/pages/settings'));
		}

		$list->addItem(lang('word_censoring'), ee('CP/URL', 'settings/word-censor'));

		$list = $sidebar->addHeader(lang('members'), ee('CP/URL', 'settings/members'))
			->addBasicList();

		$list->addItem(lang('messages'), ee('CP/URL', 'settings/messages'));
		$list->addItem(lang('avatars'), ee('CP/URL', 'settings/avatars'));

		$list = $sidebar->addHeader(lang('security_privacy'), ee('CP/URL', 'settings/security-privacy'))
			->addBasicList();

		$list->addItem(lang('access_throttling'), ee('CP/URL', 'settings/throttling'));
		$list->addItem(lang('captcha'), ee('CP/URL', 'settings/captcha'));
	}

	/**
	 * Index
	 */
	public function index()
	{
		ee()->functions->redirect(ee('CP/URL', 'settings/general'));
	}

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
			ee()->load->helper('html_helper');
			ee()->view->set_message('issue', lang('cp_message_issue'), ul($config_update), TRUE);

			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

/* End of file Settings.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controller/Settings/Settings.php */
