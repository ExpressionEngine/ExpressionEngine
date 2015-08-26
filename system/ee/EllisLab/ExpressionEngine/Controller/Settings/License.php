<?php

namespace EllisLab\ExpressionEngine\Controller\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP License Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class License extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		$vars['sections'] = array(
			array(
				array(
					'title' => 'license_contact_name',
					'desc' => 'license_contact_name_desc',
					'fields' => array(
						'license_contact_name' => array('type' => 'text')
					)
				),
				array(
					'title' => 'license_contact',
					'desc' => 'license_contact_desc',
					'fields' => array(
						'license_contact' => array('type' => 'text', 'required' => TRUE)
					)
				),
				array(
					'title' => 'license_number',
					'desc' => sprintf(lang('license_number_desc'), ee()->cp->masked_url('https://store.ellislab.com/manage')),
					'fields' => array(
						'license_number' => array('type' => 'text', 'required' => TRUE)
					)
				),
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'license_contact_name',
				 'label'   => 'lang:license_contact_name',
				 'rules'   => 'strip_tags|trim|valid_xss_check'
			),
			array(
				 'field'   => 'license_contact',
				 'label'   => 'lang:license_contact',
				 'rules'   => 'required|strip_tags|trim|valid_xss_check|valid_email'
			),
			array(
				 'field'   => 'license_number',
				 'label'   => 'lang:license_number',
				 'rules'   => 'required|callback__valid_license_pattern'
			)
		));

		$base_url = ee('CP/URL')->make('settings/license');

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveSettings($vars['sections']))
			{
				ee()->view->set_message('success', lang('license_updated'), lang('license_updated_desc'), TRUE);
			}

			ee()->functions->redirect($base_url);
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('license_and_reg_title');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Validates format of submitted license number
	 *
	 * @return bool
	 **/
	public function _valid_license_pattern($license)
	{
		$valid_pattern = valid_license_pattern($license);

		if ( ! $valid_pattern)
		{
			ee()->form_validation->set_message('_valid_license_pattern', lang('invalid_license_number'));
			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

/* End of file License.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controller/Settings/License.php */