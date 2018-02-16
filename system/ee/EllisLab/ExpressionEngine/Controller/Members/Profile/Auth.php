<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('auth_settings');
		ee()->view->save_btn_text = 'btn_authenticate_and_save';
		ee()->view->save_btn_text_working = 'btn_saving';

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
				 'field'   => 'username',
				 'label'   => 'lang:username',
				 'rules'   => 'required|valid_username'
			),
			array(
				 'field'   => 'screen_name',
				 'label'   => 'lang:screen_name',
				 'rules'   => 'required|valid_screen_name'
			),
			array(
				 'field'   => 'password',
				 'label'   => 'lang:new_password',
			),
			array(
				 'field'   => 'confirm_password',
				 'label'   => 'lang:confirm_password',
				 'rules'   => 'matches[password]'
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

			if (ee()->input->post('password'))
			{
				$this->member->password = ee()->input->post('password');
			}

			$result = $this->member->validate();

			// Display errors if there are any
			if ( ! $result->isValid())
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_save_error'))
					->addToBody(lang('settings_save_error_desc'))
					->now();

				ee()->cp->render('settings/form', $vars);
				return;
			}

			// if the password was set, need to hash it before saving and kill all other sessions
			if (ee()->input->post('password'))
			{
				ee()->load->library('auth');
				$hashed_password = ee()->auth->hash_password($this->member->password);
				$this->member->password = $hashed_password['password'];
				$this->member->salt = $hashed_password['salt'];

				ee('Model')->get('Session')
					->filter('member_id', $this->member->member_id)
					->filter('session_id', '!=', (string) ee()->session->userdata('session_id'))
					->delete();

				ee()->remember->delete_others($this->member->member_id);
			}

			$this->member->save();

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('member_updated'))
				->addToBody(lang('member_updated_desc'))
				->defer();
			ee()->functions->redirect($this->base_url);
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
