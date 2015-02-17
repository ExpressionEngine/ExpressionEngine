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
 * ExpressionEngine CP Member Profile Email Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Email extends Profile {

	private $base_url = 'members/profile/email';

	/**
	 * Email Settings
	 */
	public function index()
	{
		$this->base_url = cp_url($this->base_url, $this->query_string);

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
					'desc' => 'email_desc',
					'fields' => array(
						'email' => array('type' => 'text', 'value' => $this->member->email, 'required' => TRUE)
					)
				),
				array(
					'title' => 'smart_notifications',
					'desc' => 'smart_notifications_desc',
					'fields' => array(
						'smart_notifications' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $this->member->smart_notifications
						)
					)
				),
				array(
					'title' => 'options',
					'desc' => 'options_desc',
					'fields' => array(
						'preferences' => array(
							'type' => 'checkbox',
							'choices' => array(
								'accept_admin_email' => 'accept_admin_email',
								'accept_user_email' => 'accept_user_email',
								'notify_by_default' => 'notify_by_default',
								'notify_of_pm' => 'notify_of_pm'
							),
							'value' => $settings
						),
					)
				),
				array(
					'title' => 'current_password',
					'desc' => 'current_password',
					'fields' => array(
						'current_password' => array('type' => 'password', 'required' => TRUE)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'email',
				 'label'   => 'lang:email',
				 'rules'   => 'required|valid_email'
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
			if ($this->saveSettings($vars['sections']))
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
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Email.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Email.php */
