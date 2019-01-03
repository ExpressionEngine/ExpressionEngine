<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

/**
 * Member Profile Email Settings Controller
 */
class Email extends Settings {

	private $base_url = 'members/profile/email';

	/**
	 * Email Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		$settings = array();

		if ($this->member->accept_admin_email == 'y')
		{
			$settings[] = 'accept_admin_email';
		}

		if ($this->member->accept_user_email == 'y')
		{
			$settings[] = 'accept_user_email';
		}

		if ($this->member->notify_by_default == 'y')
		{
			$settings[] = 'notify_by_default';
		}

		if ($this->member->notify_of_pm == 'y')
		{
			$settings[] = 'notify_of_pm';
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'email',
					'fields' => array(
						'email' => array(
							'type' => 'text',
							'value' => $this->member->email,
							'required' => TRUE,
							'maxlength' => USERNAME_MAX_LENGTH,
							'attrs' => 'autocomplete="off"'
						)
					)
				),
				array(
					'title' => 'smart_notifications',
					'desc' => 'smart_notifications_desc',
					'fields' => array(
						'smart_notifications' => array(
							'type' => 'yes_no',
							'value' => $this->member->smart_notifications
						)
					)
				),
				array(
					'title' => 'email_options',
					'desc' => 'email_options_desc',
					'fields' => array(
						'preferences' => array(
							'type' => 'checkbox',
							'choices' => array(
								'accept_admin_email' => lang('accept_admin_email'),
								'accept_user_email' => lang('accept_user_email'),
								'notify_by_default' => lang('notify_by_default'),
								'notify_of_pm' => lang('notify_of_pm')
							),
							'value' => $settings
						),
					)
				)
			),
			'secure_form_ctrls' => array(
				array(
					'title' => 'existing_password',
					'desc' => 'existing_password_exp',
					'fields' => array(
						'current_password' => array(
							'type'      => 'password',
							'required' => TRUE,
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'email',
				 'label'   => 'lang:email',
				 'rules'   => 'required|valid_email|max_length['.USERNAME_MAX_LENGTH.']'
			),
			array(
				 'field'   => 'current_password',
				 'label'   => 'lang:current_password',
				 'rules'   => 'required|auth_password'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// Don't save the password check to the model
			unset($vars['sections']['secure_form_ctrls']);

			if ($this->saveSettings($vars['sections']))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($this->base_url);
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('email_settings');
		ee()->view->save_btn_text = 'btn_authenticate_and_save';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
