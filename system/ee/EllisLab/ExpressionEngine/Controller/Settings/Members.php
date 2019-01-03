<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * Members Settings Controller
 */
class Members extends Settings {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	/**
	 * General Settings
	 */
	public function index()
	{
		$groups = ee('Model')->get('MemberGroup')->order('group_title', 'asc')->all();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		ee()->load->model('member_model');
		$themes = ee('Theme')->listThemes('member');

		$member_themes = array();
		foreach ($themes as $file => $name)
		{
			$member_themes[$file] = $name;
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'allow_member_registration',
					'desc' => 'allow_member_registration_desc',
					'fields' => array(
						'allow_member_registration' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'req_mbr_activation',
					'desc' => 'req_mbr_activation_desc',
					'fields' => array(
						'req_mbr_activation' => array(
							'type' => 'radio',
							'choices' => array(
								'none' => lang('req_mbr_activation_opt_none'),
								'email' => lang('req_mbr_activation_opt_email'),
								'manual' => lang('req_mbr_activation_opt_manual')
							)
						)
					)
				),
				array(
					'title' => 'approved_member_notification',
					'desc' => 'approved_member_notification_desc',
					'fields' => array(
						'approved_member_notification' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'declined_member_notification',
					'desc' => 'declined_member_notification_desc',
					'fields' => array(
						'declined_member_notification' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'require_terms_of_service',
					'desc' => 'require_terms_of_service_desc',
					'fields' => array(
						'require_terms_of_service' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'allow_member_localization',
					'desc' => 'allow_member_localization_desc',
					'fields' => array(
						'allow_member_localization' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'default_member_group',
					'fields' => array(
						'default_member_group' => array(
							'type' => 'radio',
							'choices' => $member_groups,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('member_groups'))
							]
						)
					)
				),
				array(
					'title' => 'member_theme',
					'desc' => 'member_theme_desc',
					'fields' => array(
						'member_theme' => array(
							'type' => 'radio',
							'choices' => $member_themes
						)
					)
				)
			),
			'member_listing_settings' => array(
				array(
					'title' => 'memberlist_order_by',
					'desc' => 'memberlist_order_by_desc',
					'fields' => array(
						'memberlist_order_by' => array(
							'type' => 'radio',
							'choices' => array(
								'member_id'    => lang('id'),
								'username'     => lang('username'),
								'dates'        => lang('join_date'),
								'member_group' => lang('member_group')
							)
						)
					)
				),
				array(
					'title' => 'memberlist_sort_order',
					'desc' => 'memberlist_sort_order_desc',
					'fields' => array(
						'memberlist_sort_order' => array(
							'type' => 'radio',
							'choices' => array(
								'asc' => lang('memberlist_sort_order_opt_asc'),
								'desc' => lang('memberlist_sort_order_opt_desc')
							)
						)
					)
				),
				array(
					'title' => 'memberlist_row_limit',
					'desc' => 'memberlist_row_limit_desc',
					'fields' => array(
						'memberlist_row_limit' => array(
							'type' => 'radio',
							'choices' => array('10' => '10', '20' => '20',
								'30' => '30', '40' => '40', '50' => '50',
								'75' => '75', '100' => '100')
						)
					)
				)
			),
			'registration_notify_settings' => array(
				array(
					'title' => 'new_member_notification',
					'desc' => 'new_member_notification_desc',
					'fields' => array(
						'new_member_notification' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'mbr_notification_emails',
					'desc' => 'mbr_notification_emails_desc',
					'fields' => array(
						'mbr_notification_emails' => array('type' => 'text')
					)
				)
			)
		);

		$base_url = ee('CP/URL')->make('settings/members');

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'mbr_notification_emails',
				'label' => 'lang:mbr_notification_emails',
				'rules' => 'valid_emails'
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
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('member_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
