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
 * ExpressionEngine CP Member Profile Login Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Login extends Profile {

	private $base_url = 'members/profile/login';

	public function __construct()
	{
		parent::__construct();

		if ($this->session->userdata('group_id') != 1)
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	/**
	 * Login as index
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'redirect_to',
					'desc' => sprintf(lang('redirect_to_desc'), $this->member->screen_name),
					'fields' => array(
						'redirect' => array(
							'type' => 'radio',
							'choices' => array(
								'site_index' => lang('site_index'),
								'other' => 'other'
							),
							'value' => 'site_index'
						),
						'other' => array('type' => 'text')
					)
				)
			),
			'secure_form_ctrls' => array(
				array(
					'title' => 'existing_password',
					'desc' => 'existing_password_exp',
					'fields' => array(
						'password' => array(
							'type'      => 'password',
							'required' => TRUE,
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				)
			)
		);

		if ($this->member->getMemberGroup()->can_access_cp == 'y')
		{
			$choices =& $vars['sections'][0][0]['fields']['redirect']['choices'];
			$choices = array_slice($choices, 0 , 1, TRUE)
				+ array('cp_index' => 'cp_index')
				+ array_slice($choices, 1 , 1, TRUE);
		}

		ee('CP/Alert')->makeInline('shared-form')
			->asWarning()
			->cannotClose()
			->addToBody(sprintf(lang('login_as_warning'), $this->member->screen_name), 'warning')
			->now();

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'password',
				 'label'   => 'lang:password',
				 'rules'   => 'required'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$this->login();
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
		ee()->view->cp_page_title = sprintf(lang('login_as'), $this->member->screen_name);
		ee()->view->save_btn_text = 'btn_login';
		ee()->view->save_btn_text_working = 'btn_login_working';
		ee()->cp->render('settings/form', $vars);
	}

	private function login()
	{
		// Check password authentication
		ee()->load->library('auth');
		$validate = ee()->auth->authenticate_id(
			ee()->session->userdata['member_id'],
			ee()->input->post('password')
		);

		if ( ! $validate)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->logger->log_action(sprintf(
			lang('member_login_as'),
			$this->member->username,
			$this->member->member_id
		));

		//  Do we allow multiple logins on the same account?
		if (ee()->config->item('allow_multi_logins') == 'n')
		{
			// Kill old sessions first
			ee()->session->gc_probability = 100;
			ee()->session->delete_old_sessions();
			$expire = time() - ee()->session->session_length;

			// See if there is a current session
			// no gateway to the Session model, need to consider
			// semver implications, so using QB for now -dj 2016-10-12
			$sess_query = ee()->db->select('ip_address', 'user_agent')
				->where('member_id', $this->member->member_id)
				->where('last_activity >', $expire)
				->get('sessions');

			if ($sess_query->num_rows() > 0)
			{
				if (ee()->session->userdata['ip_address'] != $sess_query->row('ip_address') OR
					ee()->session->userdata['user_agent'] != $sess_query->row('user_agent'))
				{
					show_error(lang('multi_login_warning'));
				}
			}
		}

		$redirect = ee()->input->post('redirect');

		// Set cookie expiration to one year if the "remember me" button is clicked

		$expire = 0;
		$type = $redirect == 'cp' ? ee()->config->item('cp_session_type') : ee()->config->item('website_session_type');

		if ($type != 's')
		{
			ee()->input->set_cookie(ee()->session->c_expire , time()+$expire, $expire);
			ee()->input->set_cookie(ee()->session->c_anon , 1,  $expire);
		}

		// Create a new session
		$session_id = ee()->session->create_new_session($this->member->member_id , TRUE, TRUE);

		// Delete old password lockouts
		ee()->session->delete_password_lockout();

		// Redirect the user to the return page

		$return_path = ee()->functions->fetch_site_index();
		$url = ee()->input->post('other');

		if ( ! empty($redirect))
		{
			if ($redirect == 'cp_index')
			{
				$return_path = ee()->config->item('cp_url', FALSE).'?S='.ee()->session->session_id();
			}
			elseif ($redirect == 'other' && ! empty($url))
			{
				$return_path = ee('Security/XSS')->clean(strip_tags($url));
			}
		}

		ee()->functions->redirect($return_path);
	}
}
// END CLASS

// EOF
