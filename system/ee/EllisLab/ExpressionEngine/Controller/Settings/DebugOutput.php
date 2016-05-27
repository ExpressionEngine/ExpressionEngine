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
 * ExpressionEngine CP Debugging & Output Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class DebugOutput extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'enable_errors',
					'desc' => 'enable_errors_desc',
					'fields' => array(
						'debug' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'1' => 'enable',
								'0' => 'disable'
							)
						)
					)
				),
				array(
					'title' => 'show_profiler',
					'desc' => 'show_profiler_desc',
					'fields' => array(
						'show_profiler' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							)
						)
					)
				)
			),
			'output_options' => array(
				array(
					'title' => 'gzip_output',
					'desc' => 'gzip_output_desc',
					'fields' => array(
						'gzip_output' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'force_query_string',
					'desc' => 'force_query_string_desc',
					'fields' => array(
						'force_query_string' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'send_headers',
					'desc' => 'send_headers_desc',
					'fields' => array(
						'send_headers' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'redirect_method',
					'desc' => 'redirect_method_desc',
					'fields' => array(
						'redirect_method' => array(
							'type' => 'select',
							'choices' => array(
								'redirect' => lang('redirect_method_opt_location'),
								'refresh' => lang('redirect_method_opt_refresh')
							)
						)
					)
				),
				array(
					'title' => 'caching_driver',
					'desc' => 'caching_driver_desc',
					'fields' => array(
						'cache_driver' => ee()->cache->admin_setting()
					)
				),
				array(
					'title' => 'max_caches',
					'desc' => 'max_caches_desc',
					'fields' => array(
						'max_caches' => array('type' => 'text')
					)
				),
			)
		);

		if (extension_loaded('newrelic'))
		{
			$vars['sections']['new_relic'] = array(
				array(
					'title' => 'use_newrelic',
					'desc' => 'use_newrelic_desc',
					'fields' => array(
						'use_newrelic' => array(
							'type'  => 'yes_no',
							'value' => (ee()->config->item('use_newrelic')) ?: 'y'
						),
					)
				),
				array(
					'title' => 'newrelic_app_name',
					'desc' => 'newrelic_app_name_desc',
					'fields' => array(
						'newrelic_app_name' => array('type' => 'text')
					)
				)
			);
		}

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'max_caches',
				'label' => 'lang:max_caches',
				'rules' => 'integer'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = ee('CP/URL')->make('settings/debug-output');

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
		ee()->view->cp_page_title = lang('debugging_output');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
