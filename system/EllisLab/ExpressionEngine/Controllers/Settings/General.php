<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

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
 * ExpressionEngine CP General Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class General extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		ee()->load->model('admin_model');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'site_name',
					'desc' => 'site_name_desc',
					'fields' => array(
						'site_name' => array('type' => 'text', 'required' => TRUE)
					)
				),
				array(
					'title' => 'site_online',
					'desc' => 'site_online_desc',
					'fields' => array(
						'is_system_on' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'online',
								'n' => 'offline'
							)
						)
					)
				),
				array(
					'title' => 'version_autocheck',
					'desc' => 'version_autocheck_desc',
					'fields' => array(
						'new_version_check' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'auto',
								'n' => 'manual'
							)
						)
					),
					'action_button' => array(
						'text' => 'check_now',
						'link' => cp_url('settings/general/version-check'),
						'class' => 'version-check'
					)
				),
			),
			'defaults' => array(
				array(
					'title' => 'cp_theme',
					'desc' => '',
					'fields' => array(
						'cp_theme' => array(
							'type' => 'dropdown',
							'choices' => ee()->admin_model->get_cp_theme_list()
						)
					)
				),
				array(
					'title' => 'language',
					'desc' => 'language_desc',
					'fields' => array(
						'deft_lang' => array(
							'type' => 'dropdown',
							'choices' => ee()->lang->language_pack_names(),
							'value' => ee()->config->item('deft_lang') ?: 'english'
						)
					)
				)
			),
			'date_time_settings' => array(
				array(
					'title' => 'timezone',
					'desc' => 'timezone_desc',
					'fields' => array(
						'default_site_timezone' => array(
							'type' => 'html',
							'content' => ee()->localize->timezone_menu(set_value('default_site_timezone') ?: ee()->config->item('default_site_timezone'))
						)
					)
				),
				array(
					'title' => 'date_time_fmt',
					'desc' => 'date_time_fmt_desc',
					'fields' => array(
						'date_format' => array(
							'type' => 'dropdown',
							'choices' => array(
								'%n/%j/%y' => 'mm/dd/yy',
								'%j-%n-%y' => 'dd-mm-yy',
								'%Y-%m-%d' => 'yyyy-mm-dd'
							)
						),
						'time_format' => array(
							'type' => 'dropdown',
							'choices' => array(
								'24' => lang('24_hour'),
								'12' => lang('12_hour')
							)
						)
					)
				),
				array(
					'title' => 'include_seconds',
					'desc' => 'include_seconds_desc',
					'fields' => array(
						'include_seconds' => array('type' => 'yes_no')
					)
				),
			)
		);

		$base_url = cp_url('settings/general');

		ee()->form_validation->set_rules('site_name', 'lang:site_name', 'required|strip_tags|valid_xss_check');

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		// Handle AJAX validation
		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveSettings($vars['sections']))
			{
				ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), TRUE);
			}

			ee()->functions->redirect($base_url);
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->javascript->output("
			var versionCheckRadio = $('input[name=new_version_check]');

			EE.cp.toggleVersionCheckBtn = function(input) {

				var button = $(input).parents('fieldset').find('a.action');

				button.toggle($(input).filter(':checked').val() == 'n');
			};

			EE.cp.toggleVersionCheckBtn(versionCheckRadio);

			versionCheckRadio.click(function(event) {
				EE.cp.toggleVersionCheckBtn($(this));
			});"
		);

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('general_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * General Settings
	 */
	public function versionCheck()
	{
		ee()->load->library('el_pings');
		$details = ee()->el_pings->get_version_info();

		// Error getting version
		if ( ! $details)
		{
			ee('Alert')->makeBanner('error-getting-version')
				->asIssue()
				->withTitle(lang('cp_message_issue'))
				->addToBody(sprintf(lang('error_getting_version'), APP_VER))
				->defer();
		}
		else
		{
			end($details);
			$latest_version = current($details);

			// New version available
			if ($latest_version[0] > APP_VER)
			{
				$download_url = ee()->cp->masked_url('https://store.ellislab.com/manage');
				$instruct_url = ee()->cp->masked_url(ee()->config->item('doc_url').'installation/update.html');

				$desc = sprintf(lang('version_update_inst'), $latest_version[0], $download_url, $instruct_url);

				ee('Alert')->makeBanner('version-update-available')
					->asWarning()
					->withTitle(lang('version_update_available'))
					->addToBody($desc)
					->defer();
			}
			// Running latest version already
			else
			{
				ee('Alert')->makeBanner('running-current')
					->asSuccess()
					->withTitle(lang('running_current'))
					->addToBody(sprintf(lang('running_current_desc'), APP_VER))
					->defer();
			}
		}

		ee()->functions->redirect(cp_url('settings/general'));
	}
}
// END CLASS

/* End of file General.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/General.php */
