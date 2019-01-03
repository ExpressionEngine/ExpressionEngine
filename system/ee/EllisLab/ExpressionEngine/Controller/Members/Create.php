<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members;

use CP_Controller;

/**
 * Member Create Controller
 */
class Create extends Members {

	/**
	 * Create Member Form
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_create_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->base_url = ee('CP/URL')->make('members/create');
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

		// Get member groups who have CP access to verify the member creating this member
		$groups = $groups->filter('can_access_cp', TRUE);
		$group_toggle = array();
		foreach ($groups as $group)
		{
			$group_toggle[$group->getId()] = 'verify_password';
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'member_group',
					'desc' => 'member_group_desc',
					'fields' => array(
						'group_id' => array(
							'type' => 'radio',
							'choices' => $choices,
							'group_toggle' => $group_toggle,
							'value' => (isset($choices[5]) && $choices[5] == 'Members') ? 5 : '',
							'required' => TRUE,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('member_groups'))
							]
						)
					)
				),
				array(
					'title' => 'username',
					'fields' => array(
						'username' => array(
							'type' => 'text',
							'required' => TRUE,
							'maxlength' => USERNAME_MAX_LENGTH
						)
					)
				),
				array(
					'title' => 'mbr_email_address',
					'fields' => array(
						'email' => array(
							'type' => 'text',
							'required' => TRUE,
							'maxlength' => USERNAME_MAX_LENGTH
						)
					)
				),
				array(
					'title' => 'password',
					'desc' => 'password_desc',
					'fields' => array(
						'password' => array(
							'type' => 'password',
							'required' => TRUE,
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				),
				array(
					'title' => 'password_confirm',
					'desc' => 'password_confirm_desc',
					'fields' => array(
						'confirm_password' => array(
							'type' => 'password',
							'required' => TRUE,
							'maxlength' => PASSWORD_MAX_LENGTH
						)
					)
				)
			)
		);

		$member = ee('Model')->make('Member');
		$member->group_id = 1; // Needed to get member fields at the moment
		foreach ($member->getDisplay()->getFields() as $field)
		{
			if ($field->get('m_field_reg') == 'y' OR $field->isRequired())
			{
				$vars['sections']['custom_fields'][] = array(
					'title' => $field->getLabel(),
					'desc' => '',
					'fields' => array(
						$field->getName() => array(
							'type' => 'html',
							'content' => $field->getForm(),
							'required' => $field->isRequired(),
						)
					)
				);
			}
		}

		$vars['sections']['secure_form_ctrls'] = array(
			array(
				'title' => 'your_password',
				'desc' => 'your_password_desc',
				'group' => 'verify_password',
				'fields' => array(
					'verify_password' => array(
						'type'      => 'password',
						'required'  => TRUE,
						'maxlength' => PASSWORD_MAX_LENGTH
					)
				)
			)
		);

		if ( ! empty($_POST))
		{
			// Separate validator to validate confirm_password and verify_password
			$validator = ee('Validation')->make();
			$validator->setRules(array(
				'confirm_password' => 'matches[password]',
				'verify_password'  => 'whenGroupIdIs['.implode(',', array_keys($group_toggle)).']|authenticated'
			));

			$validator->defineRule('whenGroupIdIs', function($key, $password, $parameters, $rule)
			{
				// Don't need to validate if a member group without CP access was chosen
				return in_array($_POST['group_id'], $parameters) ? TRUE : $rule->skip();
			});

			$member->set($_POST);

			// Set some other defaults
			$member->screen_name = $_POST['username'];
			$member->ip_address = ee()->input->ip_address();
			$member->join_date = ee()->localize->now;
			$member->language = ee()->config->item('deft_lang');

			$result = $member->validate();
			$password_confirm = $validator->validate($_POST);

			// Add password confirmation failure to main result object
			if ($password_confirm->failed())
			{
				$rules = $password_confirm->getFailed();
				foreach ($rules as $field => $rule)
				{
					$result->addFailed($field, $rule[0]);
				}
			}

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				// Now that we know the password is valid, hash it
				$member->hashAndUpdatePassword($member->password);

				// -------------------------------------------
				// 'cp_members_member_create_start' hook.
				//  - Take over member creation when done through the CP
				//  - Added 1.4.2
				//
					ee()->extensions->call('cp_members_member_create_start');
					if (ee()->extensions->end_script === TRUE) return;
				//
				// -------------------------------------------

				$member->save();

				// -------------------------------------------
				// 'cp_members_member_create' hook.
				//  - Additional processing when a member is created through the CP
				//
					ee()->extensions->call('cp_members_member_create', $member->getId(), $member->getValues());
					if (ee()->extensions->end_script === TRUE) return;
				//
				// -------------------------------------------

				ee()->logger->log_action(lang('new_member_added').NBS.$member->username);
				ee()->stats->update_member_stats();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_created'))
					->addToBody(sprintf(lang('member_created_desc'), $member->username))
					->defer();

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('members/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('members'));
				}
				else
				{
					ee()->session->set_flashdata('highlight_id', $member->getId());
					ee()->functions->redirect(ee('CP/URL')->make('members/profile/settings', ['id' => $member->getId()]));
				}
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('member_not_created'))
					->addToBody(lang('member_not_created_desc'))
					->now();
			}
		}

		$this->generateSidebar('all_members');

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('members'), lang('member_manager'));

		ee()->cp->add_js_script('file', 'cp/form_group');

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('register_member');
		ee()->view->buttons = [
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save',
				'text' => 'save',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_new',
				'text' => 'save_and_new',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_close',
				'text' => 'save_and_close',
				'working' => 'btn_saving'
			]
		];
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
