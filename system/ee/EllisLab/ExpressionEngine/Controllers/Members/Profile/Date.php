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
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Profile Date Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Date extends Profile {

	private $base_url = 'members/profile/date';

	/**
	 * Date Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL', $this->base_url, $this->query_string);
		$fields = ee()->config->prep_view_vars('localization_cfg');
		$fields = $fields['fields'];
		$timezone = ee()->localize->timezone_menu($this->member->timezone, 'timezone');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'timezone',
					'desc' => 'timezone_desc',
					'fields' => array(
						'timezone' => array(
							'type' => 'html',
							'content' => $timezone
						)
					)
				),
				array(
					'title' => 'date_format',
					'desc' => 'date_format_desc',
					'fields' => array(
						'date_format' => array(
							'type' => 'select',
							'choices' => $fields['date_format']['value'],
							'value' => $this->member->date_format
						),
						'time_format' => array(
							'type' => 'select',
							'choices' => array(12 => '12 hour', 24 => '24 hour'),
							'value' => $this->member->time_format
						)
					)
				),
				array(
					'title' => 'include_seconds',
					'desc' => 'include_seconds_desc',
					'fields' => array(
						'include_seconds' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => $this->member->include_seconds
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'timezone',
				 'label'   => 'lang:timezone',
				 'rules'   => 'required'
			),
			array(
				 'field'   => 'date_format',
				 'label'   => 'lang:date_format',
				 'rules'   => 'required'
			),
			array(
				 'field'   => 'time_format',
				 'label'   => 'lang:time_format',
				 'rules'   => 'required'
			),
			array(
				 'field'   => 'include_seconds',
				 'label'   => 'lang:include_seconds',
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
		ee()->view->cp_page_title = lang('date_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Date.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Date.php */
