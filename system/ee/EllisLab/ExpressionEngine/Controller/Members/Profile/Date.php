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
 * ExpressionEngine CP Member Profile Date Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Date extends Settings {

	private $base_url = 'members/profile/date';

	/**
	 * Date Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
		$fields = ee()->config->prep_view_vars('localization_cfg');
		$fields = $fields['fields'];
		$timezone = ee()->localize->timezone_menu($this->member->timezone, 'timezone');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'site_default',
					'fields' => array(
						'site_default' => array(
							'type' => 'yes_no',
							'value' => (empty($this->member->timezone)) ? 'y' : 'n',
							'group_toggle' => array(
								'n' => 'localize'
							)
						)
					)
				),
				array(
					'title' => 'timezone',
					'group' => 'localize',
					'fields' => array(
						'timezone' => array(
							'type' => 'html',
							'content' => $timezone
						)
					)
				),
				array(
					'title' => 'date_format',
					'desc' => 'used_in_cp_only',
					'group' => 'localize',
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
					'group' => 'localize',
					'fields' => array(
						'include_seconds' => array(
							'type' => 'yes_no',
							'value' => $this->member->include_seconds
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'site_default',
				 'label'   => 'lang:site_default',
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
			$success = FALSE;
			if (ee()->input->post('site_default') == 'y')
			{
				/* @TODO Use models when models can set NULL
				$this->member->timezone = NULL;
				$this->member->date_format = NULL;
				$this->member->time_format = NULL;
				$this->member->include_seconds = NULL;
				$this->member->save();
				*/
				ee()->db->set('timezone', NULL);
				ee()->db->set('date_format', NULL);
				ee()->db->set('time_format', NULL);
				ee()->db->set('include_seconds', NULL);
				ee()->db->where('member_id', $this->member->member_id);
				ee()->db->update('members');
				$success = TRUE;
			}
			else
			{
				$success = $this->saveSettings($vars['sections']);
			}

			if ($success)
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($this->base_url);
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

		ee()->cp->add_js_script(array(
			'file' => array('cp/form_group'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('date_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
