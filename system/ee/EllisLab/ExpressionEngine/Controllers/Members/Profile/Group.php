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
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
		$this->base_url = cp_url($this->base_url, $this->query_string);
		$groups = ee()->api->get('MemberGroup')->order('group_title', 'asc')->all();
		$choices = array();

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
							'value' => $this->member->group_id
						)
					)
				),
				array(
					'title' => 'current_password',
					'desc' => 'current_password_desc',
					'fields' => array(
						'password' => array('type' => 'password')
					)
				)
			)
		);

		ee('Alert')->makeInline('shared-form')
			->asWarning()
			->cannotClose()
			->withTitle(lang('access_privilege_warning'))
			->addToBody(lang('access_privilege_caution'), 'caution')
			->now();

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'group_id',
				 'label'   => 'lang:member_group',
				 'rules'   => 'callback__valid_member_group'
			),
			array(
				 'field'   => 'password',
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
		ee()->view->cp_page_title = lang('member_group_assignment');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	public function _valid_member_group($group)
	{
		$groups = ee()->api->get('MemberGroup')->filter('group_id', $group)->all();

		if (empty($groups))
		{
			return FALSE;
		}

		return TRUE;
	}
}
// END CLASS

/* End of file MemberGroup.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/MemberGroup.php */
