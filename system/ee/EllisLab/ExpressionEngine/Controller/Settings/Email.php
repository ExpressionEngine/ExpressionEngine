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
 * ExpressionEngine CP Outgoing Email Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Email extends Settings {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_comm'))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	/**
	 * General Settings
	 */
	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'webmaster_email',
					'desc' => 'webmaster_email_desc',
					'fields' => array(
						'webmaster_email' => array('type' => 'text', 'required' => TRUE),
					)
				),
				array(
					'title' => 'webmaster_name',
					'desc' => 'webmaster_name_desc',
					'fields' => array(
						'webmaster_name' => array('type' => 'text')
					)
				),
				array(
					'title' => 'email_charset',
					'desc' => 'email_charset_desc',
					'fields' => array(
						'email_charset' => array('type' => 'text')
					)
				),
				array(
					'title' => 'mail_protocol',
					'desc' => 'mail_protocol_desc',
					'fields' => array(
						'mail_protocol' => array(
							'type' => 'select',
							'choices' => array(
								'mail' => lang('php_mail'),
								'sendmail' => lang('sendmail'),
								'smtp' => lang('smtp')
							)
						)
					)
				),
			),
			'smtp_options' => array(
				array(
					'title' => 'smtp_server',
					'desc' => 'smtp_server_desc',
					'fields' => array(
						'smtp_server' => array('type' => 'text')
					)
				),
				array(
					'title' => 'smtp_port',
					'fields' => array(
						'smtp_port' => array('type' => 'text')
					)
				),
				array(
					'title' => 'username',
					'fields' => array(
						'smtp_username' => array('type' => 'text')
					)
				),
				array(
					'title' => 'password',
					'fields' => array(
						'smtp_password' => array('type' => 'password')
					)
				),
			),
			'sending_options' => array(
				array(
					'title' => 'mail_format',
					'desc' => 'mail_format_desc',
					'fields' => array(
						'mail_format' => array(
							'type' => 'select',
							'choices' => array(
								'plain' => lang('plain_text'),
								'html' => lang('html')
							)
						)
					)
				),
				array(
					'title' => 'word_wrap',
					'desc' => 'word_wrap_desc',
					'fields' => array(
						'word_wrap' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							)
						)
					)
				),
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'webmaster_email',
				'label' => 'lang:webmaster_email',
				'rules' => 'required|valid_email'
			),
			array(
				'field' => 'webmaster_name',
				'label' => 'lang:webmaster_name',
				'rules' => 'strip_tags|valid_xss_check'
			),
			array(
				'field' => 'smtp_server',
				'label' => 'lang:smtp_server',
				'rules' => 'callback__smtp_required_field'
			),
			array(
				'field' => 'smtp_port',
				'label' => 'lang:smtp_port',
				'rules' => 'is_natural_no_zero'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = ee('CP/URL')->make('settings/email');

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

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('outgoing_email');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * A validation callback for required email configuration strings only
	 * if SMTP is the selected protocol method
	 *
	 * @access	public
	 * @param	string	$str	the string being validated
	 * @return	boolean	Whether or not the string passed validation
	 **/
	public function _smtp_required_field($str)
	{
		if (ee()->input->post('mail_protocol') == 'smtp' && trim($str) == '')
		{
			ee()->form_validation->set_message('_smtp_required_field', lang('empty_stmp_fields'));
			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

// EOF
