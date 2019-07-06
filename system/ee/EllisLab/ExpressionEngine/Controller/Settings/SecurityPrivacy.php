<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * Security & Privacy Settings Controller
 */
class SecurityPrivacy extends Settings {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_security_settings'))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'cp_session_type',
					'desc' => '',
					'fields' => array(
						'cp_session_type' => array(
							'type' => 'radio',
							'choices' => array(
								'cs' => lang('cs_session'),
								'c' => lang('c_session'),
								's' => lang('s_session')
							)
						)
					)
				),
				array(
					'title' => 'website_session_type',
					'desc' => '',
					'fields' => array(
						'website_session_type' => array(
							'type' => 'radio',
							'choices' => array(
								'cs' => lang('cs_session'),
								'c' => lang('c_session'),
								's' => lang('s_session')
							)
						)
					)
				),
				array(
					'title' => 'share_analytics',
					'desc' => 'share_analytics_desc',
					'desc' => sprintf(lang('share_analytics_desc'), ee()->cp->masked_url(DOC_URL.'cp/settings/security-privacy.html#share-analytics-with-the-expressionengine-development-team')),
					'fields' => array(
						'share_analytics' => array(
							'type' => 'yes_no'
						)
					)
				)
			),
			'cookie_settings' => array(
				array(
					'title' => 'cookie_domain',
					'desc' => 'cookie_domain_desc',
					'fields' => array(
						'cookie_domain' => array('type' => 'text')
					)
				),
				array(
					'title' => 'cookie_path',
					'desc' => sprintf(lang('cookie_path_desc'), ee()->cp->masked_url(DOC_URL.'cp/settings/security-privacy.html#path')),
					'fields' => array(
						'cookie_path' => array('type' => 'text')
					)
				),
				array(
					'title' => 'cookie_prefix',
					'desc' => lang('cookie_prefix_desc'),
					'fields' => array(
						'cookie_prefix' => array('type' => 'text')
					)
				),
				array(
					'title' => 'cookie_httponly',
					'desc' => 'cookie_httponly_desc',
					'security' => TRUE,
					'fields' => array(
						'cookie_httponly' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'cookie_secure',
					'desc' => 'cookie_secure_desc',
					'security' => TRUE,
					'fields' => array(
						'cookie_secure' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'require_cookie_consent',
					'desc' => 'require_cookie_consent_desc',
					'security' => TRUE,
					'fields' => array(
						'require_cookie_consent' => array('type' => 'yes_no')
					)
				)
			),
			'member_security_settings' => array(
				array(
					'title' => 'allow_username_change',
					'desc' => 'allow_username_change_desc',
					'fields' => array(
						'allow_username_change' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'un_min_len',
					'desc' => lang('un_min_len_desc'),
					'fields' => array(
						'un_min_len' => array('type' => 'text')
					)
				),
				array(
					'title' => 'allow_multi_logins',
					'desc' => 'allow_multi_logins_desc',
					'fields' => array(
						'allow_multi_logins' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'require_ip_for_login',
					'desc' => 'require_ip_for_login_desc',
					'fields' => array(
						'require_ip_for_login' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'password_lockout',
					'desc' => 'password_lockout_desc',
					'fields' => array(
						'password_lockout' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'password_lockout_interval',
					'desc' => lang('password_lockout_interval_desc'),
					'fields' => array(
						'password_lockout_interval' => array('type' => 'text')
					)
				),
				array(
					'title' => 'require_secure_passwords',
					'desc' => 'require_secure_passwords_desc',
					'fields' => array(
						'require_secure_passwords' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'pw_min_len',
					'desc' => 'pw_min_len_desc',
					'fields' => array(
						'pw_min_len' => array('type' => 'text')
					)
				),
				array(
					'title' => 'allow_dictionary_pw',
					'desc' => 'allow_dictionary_pw_desc',
					'fields' => array(
						'allow_dictionary_pw' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'name_of_dictionary_file',
					'desc' => 'name_of_dictionary_file_desc',
					'fields' => array(
						'name_of_dictionary_file' => array('type' => 'text')
					)
				)
			),
			'form_security_settings' => array(
				array(
					'title' => 'deny_duplicate_data',
					'desc' => 'deny_duplicate_data_desc',
					'fields' => array(
						'deny_duplicate_data' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'require_ip_for_posting',
					'desc' => 'require_ip_for_posting_desc',
					'fields' => array(
						'require_ip_for_posting' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'xss_clean_uploads',
					'desc' => 'xss_clean_uploads_desc',
					'fields' => array(
						'xss_clean_uploads' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'enable_rank_denial',
					'desc' => sprintf(lang('enable_rank_denial_desc'), 'https://support.google.com/webmasters/answer/96569?hl=en'),
					'fields' => array(
						'redirect_submitted_links' => [
							'type' => 'yes_no',
							'group_toggle' => array(
								'y' => 'force_interstitial'
							)
						]
					)
				),
				[
					'title' => 'force_interstitial',
					'desc' => 'force_interstitial_desc',
					'group' => 'force_interstitial',
					'fields' => [
						'force_redirect' => ['type' => 'yes_no']
					]
				]
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'un_min_len',
				'label' => 'lang:un_min_len',
				'rules' => 'integer'
			),
			array(
				'field' => 'password_lockout_interval',
				'label' => 'lang:password_lockout_interval',
				'rules' => 'integer'
			),
			array(
				'field' => 'pw_min_len',
				'label' => 'lang:pw_min_len',
				'rules' => 'integer'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = ee('CP/URL')->make('settings/security-privacy');

		ee('CP/Alert')->makeInline('security-tip')
			->asWarning()
			->cannotClose()
			->addToBody(lang('security_tip'))
			->addToBody(lang('security_tip_desc'), 'txt-enhance')
			->now();
		ee()->view->extra_alerts = array('security-tip');

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

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = lang('security_privacy');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
