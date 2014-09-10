<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

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
 * ExpressionEngine CP Access Throttling Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Throttling extends Settings {

	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'enable_throttling',
					'desc' => 'enable_throttling_desc',
					'fields' => array(
						'enable_throttling' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => lang('enable'),
								'n' => lang('disable')
							)
						)
					)
				),
				array(
					'title' => 'banish_masked_ips',
					'desc' => 'banish_masked_ips_desc',
					'fields' => array(
						'banish_masked_ips' => array('type' => 'yes_no')
					)
				)
			),
			'throttling_limit_settings' => array(
				array(
					'title' => 'lockout_requirement',
					'desc' => 'lockout_requirement_format',
					'type' => 'format',
					'fields' => array(
						'lockout_time' => array('type' => 'text'),
						'max_page_loads' => array('type' => 'text'),
						'time_interval' => array('type' => 'text')
					)
				),
				array(
					'title' => 'banishment_type',
					'desc' => 'banishment_type_desc',
					'fields' => array(
						'banishment_type' => array(
							'type' => 'dropdown',
							'choices' => array(
								'404' => lang('banish_404'),
								'redirect' => lang('banish_redirect'),
								'message' => lang('banish_message')
							)
						)
					)
				),
				array(
					'title' => 'banishment_url',
					'desc' => 'banishment_url_desc',
					'fields' => array(
						'banishment_url' => array('type' => 'text')
					)
				),
				array(
					'title' => 'banishment_message',
					'desc' => 'banishment_message_desc',
					'fields' => array(
						'banishment_message' => array('type' => 'textarea')
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'lockout_time',
				'label' => 'lang:lockout_time',
				'rules' => 'integer'
			),
			array(
				'field' => 'max_page_loads',
				'label' => 'lang:max_page_loads',
				'rules' => 'integer'
			),
			array(
				'field' => 'time_interval',
				'label' => 'lang:time_interval',
				'rules' => 'integer'
			)
		));

		$base_url = cp_url('settings/throttling');

		if (AJAX_REQUEST)
		{
			// These fields should be validated as a group
			$_POST['ee_fv_field'] = 'lockout_time|max_page_loads|time_interval';
			
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
		ee()->view->cp_page_title = lang('access_throttling');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_save_settings_working';

		ee()->cp->render('_shared/form', $vars);
	}
}
// END CLASS

/* End of file Throttling.php */
/* Location: ./system/expressionengine/controllers/cp/Settings/Throttling.php */