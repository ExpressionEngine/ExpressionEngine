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
 * Member Profile Auth Settings Controller
 */
class Auth extends Settings {

	private $base_url = 'members/profile/auth';

	/**
	 * Auth Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		$vars['errors'] = NULL;

		if ( ! empty($_POST))
		{
			// set and save the member as the various permissions allow
			if ($this->config->item('allow_username_change') == 'y' OR
				$this->session->userdata('group_id') == 1)
			{
				 $this->member->username = ee()->input->post('username');
			}

			// If the screen name field is empty, we'll assign is from the username field.
			if (ee()->input->post('screen_name') == '')
			{
				$this->member->screen_name = ee()->input->post('username');
			}
			else
			{
				$this->member->screen_name = ee()->input->post('screen_name');
			}

			// require authentication to change user/pass
			$validator = ee('Validation')->make();
			$validator->setRule('verify_password', 'authenticated');

			if (ee()->input->post('password'))
			{
				$this->member->password = ee()->input->post('password');
				$validator->setRule('confirm_password', 'matches[password]');
			}

			$result = $this->member->validate();
			$password_confirm = $validator->validate($_POST);

			// Add password confirmation failure to main result object
			if ($password_confirm->failed())
			{
				$rules = $password_confirm->getFailed();
				foreach ($rules as $field => $rule)
				{
					$result->addFailed($field, $rule[0]);
				}
			}

			if (AJAX_REQUEST)
			{
				return ee('Validation')->ajax($result);
			}

			if ($result->isValid())
			{
				// if the password was set, need to hash it before saving and kill all other sessions
				if (ee()->input->post('password'))
				{
					$this->member->hashAndUpdatePassword($this->member->password);
				}

				$this->member->save();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($this->base_url);
			}

			$vars['errors'] = $result;
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'username',
					'fields' => array(
						'username' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $this->member->username,
							'maxlength' => USERNAME_MAX_LENGTH,
							'attrs' => 'autocomplete="off"'
						)
					)
				),
				array(
					'title' => 'screen_name',
					'fields' => array(
						'screen_name' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $this->member->screen_name,
							'maxlength' => USERNAME_MAX_LENGTH,
							'attrs' => 'autocomplete="off"'
						)
					)
				)
			),
			'change_password' => array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('password_change_exp'))
					->cannotClose()
					->render(),
				array(
					'title' => 'new_password',
					'desc' => 'new_password_desc',
					'fields' => array(
						'password' => array(
							'type'      => 'password',
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				),
				array(
					'title' => 'new_password_confirm',
					'desc' => 'new_password_confirm_desc',
					'fields' => array(
						'confirm_password' => array(
							'type'      => 'password',
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				)
			),
			'secure_form_ctrls' => array(
				array(
					'title' => 'existing_password',
					'desc' => 'existing_password_exp',
					'fields' => array(
						'verify_password' => array(
							'type'      => 'password',
							'required' => TRUE,
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				)
			)
		);

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('auth_settings');
		ee()->view->save_btn_text = 'btn_authenticate_and_save';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
