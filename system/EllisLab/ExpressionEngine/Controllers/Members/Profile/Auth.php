<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

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
 * ExpressionEngine CP Member Profile Auth Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Auth extends Profile {

	private $base_url = 'members/profile/auth';

	/**
	 * Auth Settings
	 */
	public function index()
	{
		$this->base_url = cp_url($this->base_url, $this->query_string);

		$saved = array(
			array(
				array(
					'title' => 'username',
					'desc' => 'username_desc',
					'fields' => array(
						'username' => array('type' => 'text', 'value' => $this->member->username)
					)
				),
				array(
					'title' => 'screen_name',
					'desc' => 'screen_name_desc',
					'fields' => array(
						'screen_name' => array('type' => 'text', 'value' => $this->member->screen_name)
					)
				),
				array(
					'title' => 'new_password',
					'desc' => 'new_password_desc',
					'fields' => array(
						'password' => array('type' => 'password')
					)
				)
			)
		);

		$vars['sections'] = array_merge($saved, array(
			array(
				array(
					'title' => 'confirm_password',
					'desc' => 'confirm_password_desc',
					'fields' => array(
						'confirm_password' => array('type' => 'password')
					)
				),
				array(
					'title' => 'current_password',
					'desc' => 'current_password',
					'fields' => array(
						'current_password' => array('type' => 'password')
					)
				)
			)
		));

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
				 'label'   => 'lang:new_password'
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
			if ($this->saveSettings($saved))
			{
				ee()->view->set_message('success', lang('member_updated'), lang('member_updated_desc'), TRUE);
				ee()->functions->redirect($base_url);
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('email_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_save_settings_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Auth.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Auth.php */
