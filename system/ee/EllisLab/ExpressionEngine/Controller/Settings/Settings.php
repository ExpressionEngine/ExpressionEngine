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
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Settings extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		ee('CP/Alert')->makeDeprecationNotice()->now();

		if ( ! ee()->cp->allowed_group('can_access_sys_prefs'))
		{
			show_error(lang('unauthorized_access'), 403);
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

		$list = $sidebar->addHeader(lang('general_settings'), ee('CP/URL')->make('settings/general'))
			->addBasicList();

		$list->addItem(lang('license_and_registration'), ee('CP/URL')->make('settings/license'));
		$list->addItem(lang('url_path_settings'), ee('CP/URL')->make('settings/urls'));

		if (ee()->cp->allowed_group('can_access_comm'))
		{
			$list->addItem(lang('outgoing_email'), ee('CP/URL')->make('settings/email'));
		}

		$list->addItem(lang('debugging_output'), ee('CP/URL')->make('settings/debug-output'));

		$content_and_design_link = NULL;

		if (ee()->cp->allowed_group('can_admin_channels'))
		{
			$content_and_design_link = ee('CP/URL')->make('settings/content-design');
		}

		$list = $sidebar->addHeader(lang('content_and_design'), $content_and_design_link)
			->addBasicList();

		if (ee()->cp->allowed_group('can_access_addons', 'can_admin_addons'))
		{
			$list->addItem(lang('comment_settings'), ee('CP/URL')->make('settings/comments'));
		}

		$list->addItem(lang('html_buttons'), ee('CP/URL')->make('settings/buttons'));

		if (ee()->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			$list->addItem(lang('template_settings'), ee('CP/URL')->make('settings/template'));
		}

		$list->addItem(lang('hit_tracking'), ee('CP/URL')->make('settings/hit-tracking'));

		if (ee()->addons_model->module_installed('pages'))
		{
			$list->addItem(lang('pages_settings'), ee('CP/URL')->make('addons/settings/pages/settings'));
		}

		$list->addItem(lang('word_censoring'), ee('CP/URL')->make('settings/word-censor'));
		$list->addItem(lang('menu_manager'), ee('CP/URL')->make('settings/menu-manager'));

		if (ee()->cp->allowed_group('can_access_members', 'can_admin_mbr_groups'))
		{
			$list = $sidebar->addHeader(lang('members'), ee('CP/URL')->make('settings/members'))
				->addBasicList();

			$list->addItem(lang('messages'), ee('CP/URL')->make('settings/messages'));
			$list->addItem(lang('avatars'), ee('CP/URL')->make('settings/avatars'));
		}

		if (ee()->cp->allowed_group('can_access_security_settings'))
		{
			$list = $sidebar->addHeader(lang('security_privacy'), ee('CP/URL')->make('settings/security-privacy'))
				->addBasicList();

			$list->addItem(lang('access_throttling'), ee('CP/URL')->make('settings/throttling'));
			$list->addItem(lang('captcha'), ee('CP/URL')->make('settings/captcha'));
		}
	}

	/**
	 * Index
	 */
	public function index()
	{
		$landing = ee('CP/URL')->make('settings');

		// Redirect to the first section they have permission
			$settings_options = array(
				'can_access_sys_prefs' => ee('CP/URL')->make('settings/general'),
				'can_admin_design' => ee('CP/URL')->make('settings/content-design'),
				'can_access_members' => ee('CP/URL')->make('settings/members'),
				'can_access_security_settings' => ee('CP/URL')->make('settings/security-privacy')
				);

			foreach ($settings_options as $allow => $link)
			{
				if (ee()->cp->allowed_group($allow))
				{
					$landing = $link;
					break;
				}
			}

		ee()->functions->redirect($landing);
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
					if (isset($field['save_in_config']) && $field['save_in_config'] === FALSE)
					{
						continue;
					}

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

// EOF
