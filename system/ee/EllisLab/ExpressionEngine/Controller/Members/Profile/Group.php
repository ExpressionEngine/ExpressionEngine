<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

/**
 * Member Group Settings Controller
 */
class Group extends Profile {

	private $base_url = 'members/profile/group';

	public function __construct()
	{
		parent::__construct();

		if ($this->member->member_id == ee()->session->userdata['member_id']
		    && $this->member->group_id == 1)
		{
			show_error(lang('cannot_change_your_group'));
		}
	}

	/**
	 * Member Group assignment
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
		$groups = ee('Model')->get('MemberGroup')->order('group_title', 'asc')->all();
		$choices = array();

		if (ee()->session->userdata('group_id') != 1)
		{
			$groups = $groups->filter('is_locked', FALSE);
		}

		foreach ($groups as $group)
		{
			$choices[$group->group_id] = $group->group_title;
		}

		if ( ! array_key_exists($this->member->group_id, $choices))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$vars['sections'] = array(
			array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('access_privilege_warning'))
					->addToBody(
						sprintf(lang('access_privilege_caution'), '<span class="icon--caution" title="exercise caution"></span>'),
						'caution'
					)
					->cannotClose()
					->render(),
				array(
					'title' => 'member_group',
					'desc' => 'member_group_desc',
					'caution' => TRUE,
					'fields' => array(
						'group_id' => array(
							'type' => 'radio',
							'choices' => $choices,
							'value' => $this->member->group_id,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('member_groups'))
							]
						)
					)
				)
			)
		);

		$rules = [
			[
				'field' => 'group_id',
				'label' => 'lang:member_group',
				'rules' => 'callback__valid_member_group'
			]
		];

		if ( ! ee('Session')->isWithinAuthTimeout())
		{
			$vars['sections']['secure_form_ctrls'] = array(
				array(
					'title' => 'existing_password',
					'desc' => 'existing_password_exp',
					'fields' => array(
						'password_confirm' => array(
							'type'      => 'password',
							'required'  => TRUE,
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				)
			);

			$rules[] = [
				'field' => 'password_confirm',
				'label' => 'lang:password',
				'rules' => 'required|auth_password[useAuthTimeout]'
			];

		}

		ee()->form_validation->set_rules($rules);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// Don't try to save the password confirm
			if ($this->saveSettings(array_slice($vars['sections'], 0, 1)))
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

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('member_group_assignment');
		ee()->view->save_btn_text = 'btn_authenticate_and_save';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	public function _valid_member_group($group)
	{
		$groups = ee('Model')->get('MemberGroup')->filter('group_id', $group);

		if (ee()->session->userdata('group_id') != 1)
		{
			$groups->filter('is_locked', 'n');
		}

		$num_groups = $groups->count();

		if ($num_groups == 0)
		{
			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

// EOF
