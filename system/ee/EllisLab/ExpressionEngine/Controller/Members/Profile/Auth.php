<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

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
 * ExpressionEngine CP Member Profile Auth Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Auth extends Settings {

	private $base_url = 'members/profile/auth';

	/**
	 * Auth Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'username',
					'fields' => array(
						'username' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $this->member->username
						)
					)
				),
				array(
					'title' => 'screen_name',
					'fields' => array(
						'screen_name' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $this->member->screen_name
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
			if ($this->update())
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($base_url);
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
		ee()->view->cp_page_title = lang('auth_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	/**
	  *	 Update username and password
	  */
	function update()
	{
		if ($this->config->item('allow_username_change') != 'y' &&
			$this->session->userdata('group_id') != 1)
		{
			$_POST['username'] = $this->member->username;
		}

		// validate for unallowed blank values
		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		// If the screen name field is empty, we'll assign is from the username field.
		if ($_POST['screen_name'] == '')
		{
			$_POST['screen_name'] = $_POST['username'];
		}

		// Fetch member data
		$query = $this->member_model->get_member_data($this->member->member_id, array('username', 'screen_name'));

		$this->VAL = $this->_validate_user(array(
			'username'			=> $this->input->post('username'),
			'cur_username'		=> $query->row('username'),
			'screen_name'		=> $this->input->post('screen_name'),
			'cur_screen_name'	=> $query->row('screen_name'),
			'password'			=> $this->input->post('password'),
			'password_confirm'	=> $this->input->post('confirm_password'),
			'cur_password'		=> $this->input->post('current_password')
		));

		$this->VAL->validate_screen_name();

		if ($this->config->item('allow_username_change') == 'y' OR
			$this->session->userdata('group_id') == 1)
		{
			$this->VAL->validate_username();
		}

		if ($_POST['password'] != '')
		{
			$this->VAL->validate_password();
		}

		// Display errors if there are any
		if (count($this->VAL->errors) > 0)
		{
			show_error($this->VAL->show_errors());
		}

		// Update "last post" forum info if needed
		if ($query->row('screen_name') != $_POST['screen_name'] &&
			$this->config->item('forum_is_installed') == "y")
		{
			$this->db->where('forum_last_post_author_id', $this->member->member_id);
			$this->db->update(
				'forums',
				array('forum_last_post_author' => $this->input->post('screen_name'))
			);

			$this->db->where('mod_member_id', $this->member->member_id);
			$this->db->update(
				'forum_moderators',
				array('mod_member_name' => $this->input->post('screen_name'))
			);
		}

		// Assign the query data
		$data['screen_name'] = $_POST['screen_name'];

		if ($this->config->item('allow_username_change') == 'y' OR $this->session->userdata('group_id') == 1)
		{
			$data['username'] = $_POST['username'];
		}

		if ($_POST['password'] != '')
		{
			$this->load->library('auth');

			$this->auth->update_password($this->member->member_id, $this->input->post('password'));
		}

		$this->member_model->update_member($this->member->member_id, $data);

		if (ee()->config->item('enable_comments') == 'y')
		{
			if ($query->row('screen_name') != $_POST['screen_name'])
			{
				$query = $this->member_model->get_member_data($this->member->member_id, array('screen_name'));

				$screen_name = ($query->row('screen_name')	!= '') ? $query->row('screen_name')	 : '';

				// Update comments with current member data
				$data = array('name' => ($screen_name != '') ? $screen_name : $_POST['username']);

				$this->db->where('author_id', $this->member->member_id);
				$this->db->update('comments', $data);
			}
		}

		// Write log file
		$this->logger->log_action($this->VAL->log_msg);

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('member_updated'))
			->addToBody(lang('member_updated_desc'))
			->defer();
		ee()->functions->redirect($this->base_url);
	}

	/**
	 * Validate either a user, or a Super Admin editing the user
	 * @param  array $validation_data Validation data to be sent to EE_Validate
	 * @return EE_Validate	Validation object returned from EE_Validate
	 */
	private function _validate_user($validation_data)
	{
		//	Validate submitted data
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate.php';
		}

		$defaults = array(
			'member_id'		=> $this->member->member_id,
			'val_type'		=> 'update', // new or update
			'fetch_lang'	=> FALSE,
			'require_cpw'	=> TRUE,
			'enable_log'	=> TRUE,
		);

		$validation_data = array_merge($defaults, $validation_data);

		// Are we dealing with a Super Admin editing someone else's account?
		if ($this->session->userdata('group_id') == 1)
		{
			// Validate Super Admin's password
			$this->load->library('auth');
			$auth = $this->auth->authenticate_id(
				$this->session->userdata('member_id'),
				$this->input->post('current_password')
			);

			if ($auth === FALSE)
			{
				show_error(lang('invalid_password'));
			}

			// Make sure we don't verify the actual member's existing password
			$validation_data['require_cpw'] = FALSE;
		}

		return new \EE_Validate($validation_data);
	}
}
// END CLASS

// EOF
