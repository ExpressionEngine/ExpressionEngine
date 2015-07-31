<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

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
 * ExpressionEngine CP Member Group Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Group extends Profile {

	private $base_url = 'members/profile/group';

	/**
	 * Member Group assignment
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL', $this->base_url, $this->query_string);
		$groups = ee()->api->get('MemberGroup')->order('group_title', 'asc')->all();
		$choices = array();

		foreach ($groups as $group)
		{
			$choices[$group->group_id] = $group->group_title;
		}

		$vars['sections'] = array(
			array(
				ee('Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('access_privilege_warning'))
					->addToBody(
						sprintf(lang('access_privilege_caution'), '<span title="excercise caution"></span>'),
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
							'type' => 'select',
							'choices' => $choices,
							'value' => $this->member->group_id
						)
					)
				),
				array(
					'title' => 'existing_password',
					'desc' => 'existing_password_exp',
					'fields' => array(
						'password_confirm' => array('type' => 'password')
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'group_id',
				 'label'   => 'lang:member_group',
				 'rules'   => 'callback__valid_member_group'
			),
			array(
				 'field'   => 'password_confirm',
				 'label'   => 'lang:password',
				 'rules'   => 'required|auth_password'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// Don't try to save the password confirm
			array_pop($vars['sections'][0]);

			if ($this->saveSettings($vars['sections']))
			{
				ee()->view->set_message('success', lang('member_updated'), lang('member_updated_desc'), TRUE);
				ee()->functions->redirect($this->base_url);
			}

		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('member_group_assignment');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	public function _valid_member_group($group)
	{
		$groups = ee()->api->get('MemberGroup')->filter('group_id', $group)->count();

		if ($groups == 0)
		{
			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

/* End of file MemberGroup.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/MemberGroup.php */
