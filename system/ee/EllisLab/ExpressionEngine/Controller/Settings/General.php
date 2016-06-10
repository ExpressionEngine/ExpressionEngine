<?php

namespace EllisLab\ExpressionEngine\Controller\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

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
 * ExpressionEngine CP General Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class General extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		ee()->load->model('admin_model');

		$site = ee('Model')->get('Site')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		$localization_fields = ee()->config->prep_view_vars('localization_cfg');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'site_name',
					'fields' => array(
						'site_name' => array(
							'type' => 'text',
							'value' => $site->site_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'site_short_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'site_short_name' => array(
							'type' => 'text',
							'value' => $site->site_name,
							'required' => TRUE
						)
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
						),
						'action_button' => array(
							'type' => 'action_button',
							'text' => 'check_now',
							'link' => ee('CP/URL', 'settings/general/version-check'),
							'class' => 'version-check',
							'save_in_config' => FALSE
						)
					)
				),
				array(
					'title' => 'enable_msm',
					'desc' => 'enable_msm_desc',
					'fields' => array(
						'multiple_sites_enabled' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							)
						)
					)
				),
			),
			'defaults' => array(
				array(
					'title' => 'language',
					'desc' => 'used_in_cp_only',
					'fields' => array(
						'deft_lang' => array(
							'type' => 'select',
							'choices' => ee()->lang->language_pack_names(),
							'value' => ee()->config->item('deft_lang') ?: 'english'
						)
					)
				)
			),
			'date_time_settings' => array(
				array(
					'title' => 'timezone',
					'fields' => array(
						'default_site_timezone' => array(
							'type' => 'html',
							'content' => ee()->localize->timezone_menu(set_value('default_site_timezone') ?: ee()->config->item('default_site_timezone'))
						)
					)
				),
				array(
					'title' => 'date_time_fmt',
					'desc' => 'used_in_cp_only',
					'fields' => array(
						'date_format' => array(
							'type' => 'select',
							'choices' => $localization_fields['fields']['date_format']['value']
						),
						'time_format' => array(
							'type' => 'select',
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

		$base_url = ee('CP/URL', 'settings/general');

		ee()->form_validation->set_rules('site_name', 'lang:site_name', 'required|strip_tags|valid_xss_check');
		ee()->form_validation->set_rules('site_short_name', 'lang:site_short_name', 'required|alpha_dash|strip_tags|callback__validShortName|valid_xss_check');

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
	 * Ensure the short name is valid
	 * @param  string $short_name Short name for the site
	 * @return boolean            TRUE if valid, FALSE if not
	 */
	public function _validShortName($short_name)
	{
		$count = ee('Model')->get('Site')
			->filter('site_id', '!=', ee()->config->item('site_id'))
			->filter('site_name', $short_name)
			->count();

		if ($count > 0)
		{
			ee()->form_validation->set_message(
				'_validShortName',
				lang('site_short_name_taken')
			);
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Save the settings from General, but make sure to save site name and label
	 * manually
	 *
	 * @param	array	$sections	Array of sections passed to form view
	 * @return	bool	Success or failure of saving the settings
	 */
	protected function saveSettings($sections)
	{
		// Remove site_name/label
		$site = ee('Model')->get('Site')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		// Site_name is the version of the name that's used in parameters and
		// must be all one word, no spaces. site_label is the version that's
		// more outward facing.
		$site->site_name = ee()->input->post('site_short_name');
		$site->site_label = ee()->input->post('site_name');
		$site->save();

		unset($sections[0][0]);
		unset($sections[0][1]);

		return parent::saveSettings($sections);
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
			ee('CP/Alert')->makeBanner('error-getting-version')
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
				$instruct_url = ee()->cp->masked_url(DOC_URL.'installation/update.html');

				$desc = sprintf(lang('version_update_inst'), $latest_version[0], $download_url, $instruct_url);

				ee('CP/Alert')->makeBanner('version-update-available')
					->asWarning()
					->withTitle(lang('version_update_available'))
					->addToBody($desc)
					->defer();
			}
			// Running latest version already
			else
			{
				ee('CP/Alert')->makeBanner('running-current')
					->asSuccess()
					->withTitle(lang('running_current'))
					->addToBody(sprintf(lang('running_current_desc'), APP_VER))
					->defer();
			}
		}

		ee()->functions->redirect(ee('CP/URL', 'settings/general'));
	}
}
// END CLASS

// EOF
