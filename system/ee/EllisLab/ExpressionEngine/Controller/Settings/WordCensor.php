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
 * ExpressionEngine CP Word Censoring Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class WordCensor extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		ee()->load->model('admin_model');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'enable_censoring',
					'desc' => 'enable_censoring_desc',
					'fields' => array(
						'enable_censoring' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							)
						)
					)
				),
				array(
					'title' => 'censor_replacement',
					'desc' => 'censor_replacement_desc',
					'fields' => array(
						'censor_replacement' => array('type' => 'text')
					)
				),
				array(
					'title' => 'censored_words',
					'desc' => 'censored_words_desc',
					'fields' => array(
						'censored_words' => array(
							'type' => 'textarea',
							'kill_pipes' => TRUE
						)
					)
				)
			)
		);

		$base_url = ee('CP/URL')->make('settings/word-censor');

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'censor_replacement',
				'label' => 'lang:censor_replacement',
				'rules' => 'strip_tags|valid_xss_check'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

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
		ee()->view->cp_page_title = lang('word_censoring');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
