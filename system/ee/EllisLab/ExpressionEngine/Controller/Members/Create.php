<?php

namespace EllisLab\ExpressionEngine\Controller\Members;

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
 * ExpressionEngine CP Member Create Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Create extends Members {

	private $base_url = 'members/create';

	/**
	 * Create Member Form
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_create_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->hasMaximumMembers())
		{
			show_error(lang('maximum_members_reached'));
		}

		$this->base_url = ee('CP/URL')->make($this->base_url);
		$groups = ee()->api->get('MemberGroup')->order('group_title', 'asc')->all();
		$choices = array();

		if (ee()->session->userdata('group_id') != 1)
		{
			$groups = $groups->filter('is_locked', FALSE);
		}

		foreach ($groups as $group)
		{
			$choices[$group->group_id] = $group->group_title;
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'member_group',
					'desc' => 'member_group_desc',
					'fields' => array(
						'group_id' => array(
							'type' => 'select',
							'choices' => $choices,
							'value' => (isset($choices[5]) && $choices[5] == 'Members') ? 5 : '',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'username',
					'fields' => array(
						'username' => array('type' => 'text', 'required' => TRUE)
					)
				),
				array(
					'title' => 'mbr_email_address',
					'fields' => array(
						'email' => array('type' => 'text', 'required' => TRUE)
					)
				),
				array(
					'title' => 'password',
					'desc' => 'password_desc',
					'fields' => array(
						'password' => array('type' => 'password', 'required' => TRUE)
					)
				),
				array(
					'title' => 'password_confirm',
					'desc' => 'password_confirm_desc',
					'fields' => array(
						'confirm_password' => array('type' => 'password', 'required' => TRUE)
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

		// Separate validator to validate confirm_password
		$validator = ee('Validation')->make();
		$validator->setRules(array(
			'confirm_password' => 'matches[password]'
		));

		if ( ! empty($_POST))
		{
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
				$rules = $password_confirm->getFailed('confirm_password');
				$result->addFailed('confirm_password', $rules[0]);
			}

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				// Now that we know the password is valid, hash it
				ee()->load->library('auth');
				$hashed_password = ee()->auth->hash_password($member->password);
				$member->password = $hashed_password['password'];
				$member->salt = $hashed_password['salt'];

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

				ee()->session->set_flashdata('highlight_id', $member->getId());

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_created'))
					->addToBody(sprintf(lang('member_created_desc'), $member->username))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('members', array('sort_col' => 'member_id', 'sort_dir' => 'desc')));
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

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('register_member');
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('member'));
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
