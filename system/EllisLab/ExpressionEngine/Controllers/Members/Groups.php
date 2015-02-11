<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Controllers\Members;

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
 * ExpressionEngine CP Members Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Groups extends Members\Members {

	private $base_url;
	private $site_id;
	private $super_admin;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->base_url = new URL('members/groups', ee()->session->session_id());
		$this->site_id = ee()->config->item('site_id');
		$this->super_admin = $this->session->userdata('group_id') == 1;
	}

	/**
	 * Group List Index
	 */
	public function index()
	{
		$perpage = $this->config->item('memberlist_row_limit');
		$sort_col = ee()->input->get('sort_col') ?: 'group_id';
		$sort_col = ($sort_col == 'id') ? 'group_id' : $sort_col;
		$sort_dir = ee()->input->get('sort_dir') ?: 'asc';
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;
		$offset = ! empty($page) ? ($page - 1) * $perpage : 0;

		$table = Table::create(array(
			'sort_col' => $sort_col,
			'sort_dir' => $sort_dir,
			'limit' => $perpage
		));

		$table->setColumns(
			array(
				'id' => array(
					'type'	=> Table::COL_ID
				),
				'group_title',
				'is_locked',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$data = array();
		$groupData = array();
		$total = ee()->api->get('MemberGroup')->count();
		$groups = ee()->api->get('MemberGroup')->order($sort_col, $sort_dir)->limit($perpage)->offset($offset);

		if ( ! empty($search = ee()->input->post('search')))
		{
			$groups = $groups->filter('group_title', 'LIKE', "%$search%");
		}

		$groups = $groups->all();

		foreach ($groups as $group)
		{
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => cp_url('members/groups/edit/', array('group' => $group->group_id)),
					'title' => strtolower(lang('edit'))
				)
			));

			$status = ($group->is_locked == 'y') ? 'locked' : 'unlocked';
			$status = "<span class='st-$status'>" . lang($status) . "</span>";
			$count = $group->getMembers()->count();
			$href = cp_url('members', array('group' => $group->group_id));
			$title = "$group->group_title <a href='$href' alt='" . lang('view_members') . $group->group_title ."'>($count)</a>";

			$groupData[] = array(
				'id' => $group->group_id,
				'title' => $title,
				'is_locked' => $status,
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $group->group_id,
					'data'	=> array(
						'confirm' => lang('group') . ': <b>' . htmlentities($group->group_title, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setNoResultsText('no_search_results');
		$table->setData($groupData);
		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = cp_url('members/groups/delete');

		$base_url = $data['table']['base_url'];

		if ( ! empty($data['table']['data']))
		{
			$pagination = new Pagination(
				$perpage,
				$total,
				$page
			);
			$data['pagination'] = $pagination->cp_links($base_url);
		}

		// Set search results heading
		if ( ! empty($data['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$data['table']['total_rows'],
				$data['table']['search']
			);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('all_member_groups');
		ee()->cp->render('members/member_group_manager', $data);
	}

	public function create()
	{
		$vars = array(
			'cp_page_title' => lang('create_member_group'),
			'save_btn_text' => lang('save_member_group')
		);

		$this->form($vars);
	}

	public function edit()
	{
		$vars = array(
			'cp_page_title' => lang('edit_member_group'),
			'save_btn_text' => lang('save_member_group')
		);

		$group = ee()->api->get('MemberGroup', array(ee()->input->get('group')))->first();
		$current = $this->groupData($group);

		$this->form($vars, $current);
	}

	public function delete()
	{
	}

	private function form($vars = array(), $values = array())
	{
		if ( ! $this->super_admin)
		{
			show_error(lang('only_superadmins_can_admin_groups'));
		}

		$template_groups = array();
		$groups = ee()->api->get('TemplateGroup')->all();

		foreach ($groups as $group)
		{
			$template_groups[$group->group_id] = $group->group_name;
		}

		$allowed_channels = array();
		$channels = ee()->api->get('Channel')->all();

		foreach ($channels as $channel)
		{
			$allowed_channels[$channel->channel_id] = $channel->channel_name;
		}

		var_dump($values);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'group_title' => array(
							'type' => 'text',
							'value' => element('group_title', $values),
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'description',
					'desc' => 'description_desc',
					'fields' => array(
						'group_description' => array(
							'type' => 'textarea',
							'value' => element('group_description', $values),
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'security_lock',
					'desc' => 'lock_description',
					'fields' => array(
						'require_field' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => element('is_locked', $values)
						)
					)
				),
			),
			'general_access' => array(
				array(
					'title' => 'site_access',
					'desc' => 'site_access_description',
					'fields' => array(
						'website_access' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_view_online_system' => 'online',
								'can_view_offline_system' => 'offline'
							),
							'value' => element('site_access', $values)
						),
					)
				),
				array(
					'title' => 'access_public_profiles',
					'desc' => 'access_public_profiles_desc',
					'fields' => array(
						'can_view_profiles' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_view_profiles', $values)
						)
					)
				),
				array(
					'title' => 'email_other_members',
					'desc' => 'email_other_members_desc',
					'fields' => array(
						'can_send_email' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_send_email', $values)
						)
					)
				),
				array(
					'title' => 'delete_account',
					'desc' => 'delete_account_desc',
					'fields' => array(
						'can_delete_self' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_delete_self', $values)
						)
					)
				),
				array(
					'title' => 'delete_notifications',
					'desc' => 'delete_notifications_desc',
					'fields' => array(
						'mbr_delete_notify_emails' => array(
							'type' => 'text',
							'value' => element('mbr_delete_notify_emails', $values)
						)
					)
				),
				array(
					'title' => 'include_members_in',
					'desc' => 'include_members_in_desc',
					'fields' => array(
						'include_members_in' => array(
							'type' => 'checkbox',
							'choices' => array(
								'include_in_authorlist' => 'author_lists',
								'include_in_memberlist' => 'member_lists',
								'include_in_mailinglists' => 'mailing_lists'
							),
							'value' => element('include_members_in', $values)
						),
					)
				)
			),
			'commenting' => array(
				array(
					'title' => 'submit_comments',
					'desc' => 'submit_comments_desc',
					'fields' => array(
						'can_post_comments' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_post_comments', $values)
						)
					)
				),
				array(
					'title' => 'bypass_moderation',
					'desc' => 'bypass_moderation_desc',
					'fields' => array(
						'exclude_from_moderation' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('exclude_from_moderation', $values)
						)
					)
				),
				array(
					'title' => 'comment_actions',
					'desc' => 'comment_actions_desc',
					'caution' => TRUE,
					'fields' => array(
						'comment_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_edit_own_comments' => 'edit_own_comments',
								'can_delete_own_comments' => 'delete_own_comments',
								'can_edit_all_comments' => 'edit_others',
								'can_delete_all_comments' => 'delete_others'
							),
							'value' => element('comment_actions', $values)
						),
					)
				)
			),
			'search' => array(
				array(
					'title' => 'access_search',
					'desc' => 'access_search_desc',
					'fields' => array(
						'can_search' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_search', $values)
						)
					)
				),
				array(
					'title' => 'search_limit',
					'desc' => 'search_limit_desc',
					'fields' => array(
						'search_flood_control' => array(
							'type' => 'text',
							'value' => element('search_flood_control', $values)
						)
					)
				)
			),
			'personal_messaging' => array(
				array(
					'title' => 'access_personal_messages',
					'desc' => 'access_personal_messages_desc',
					'fields' => array(
						'can_send_private_messages' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_send_private_messages', $values)
						)
					)
				),
				array(
					'title' => 'per_day_max',
					'desc' => 'per_day_max_desc',
					'fields' => array(
						'prv_msg_send_limit' => array(
							'type' => 'text',
							'value' => element('prv_msg_send_limit', $values)
						)
					)
				),
				array(
					'title' => 'storage_max',
					'desc' => 'storage_max_desc',
					'fields' => array(
						'prv_msg_storage_limit' => array(
							'type' => 'text',
							'value' => element('prv_msg_storage_limit', $values)
						)
					)
				),
				array(
					'title' => 'allow_attachments',
					'desc' => 'allow_attachments_desc',
					'fields' => array(
						'can_attach_in_private_messages' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_attach_in_private_messages', $values)
						)
					)
				),
				array(
					'title' => 'access_bulletins',
					'desc' => 'access_bulletins_desc',
					'fields' => array(
						'can_send_bulletins' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_send_bulletins', $values)
						)
					)
				)
			),
			'control_panel' => array(
				array(
					'title' => 'access_control_panel',
					'desc' => 'access_control_panel_desc',
					'fields' => array(
						'can_access_cp' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_access_cp', $values)
						)
					)
				)
			),
			'channels' => array(
				array(
					'title' => 'can_admin_channels',
					'desc' => 'can_admin_channels_desc',
					'fields' => array(
						'can_admin_channels' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'yes',
								'n' => 'no'
							),
							'value' => element('can_admin_channels', $values)
						)
					)
				),
				array(
					'title' => 'categories',
					'desc' => 'categories_desc',
					'fields' => array(
						'category_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_edit_categories' => 'edit_categories',
								'can_delete_categories' => 'delete_categories'
							),
							'value' => element('category_actions', $values)
						),
					)
				)
			),
			'channel_entries_management' => array(
				array(
					'title' => 'allowed_actions',
					'desc' => 'allowed_actions_desc',
					'fields' => array(
						'channel_entry_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'create_entries' => 'create_entries',
								'edit_own_entries' => 'edit_own_entries',
								'can_delete_self_entries' => 'delete_own_entries',
								'can_edit_other_entries' => 'edit_other_entries',
								'can_delete_all_entries' => 'delete_other_entries',
								'can_assign_post_authors' => 'change_author'
							),
							'value' => element('channel_entry_actions', $values)
						),
					)
				),
				array(
					'title' => 'allowed_channels',
					'desc' => 'allowed_channels_desc',
					'fields' => array(
						'allowed_channels' => array(
							'type' => 'checkbox',
							'choices' => $allowed_channels,
							'value' => element('allowed_channels', $values)
						),
					)
				)
			),
			'members' => array(
				array(
					'title' => 'members',
					'desc' => 'members_desc',
					'fields' => array(
						'member_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_admin_members' => 'create_members',
								'edit_members' => 'edit_members',
								'can_delete_members' => 'delete_members',
								'can_ban_users' => 'ban_members',
								'change_groups' => 'change_groups'
							),
							'value' => element('member_actions', $values)
						)
					)
				)
			),
			'design' => array(
				array(
					'title' => 'manage_design_settings',
					'desc' => 'manage_design_settings',
					'fields' => array(
						'require_field' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => element('manage_design_settings', $values)
						)
					)
				),
				array(
					'title' => 'template_groups',
					'desc' => 'template_groups_desc',
					'fields' => array(
						'template_groups' => array(
							'type' => 'checkbox',
							'choices' => array(
								'create_groups' => 'create_groups',
								'edit_groups' => 'edit_groups',
								'delete_groups' => 'delete_groups'
							),
							'value' => element('template_group_actions', $values)
						),
					)
				),
				array(
					'title' => 'snippets',
					'desc' => 'snippets_desc',
					'fields' => array(
						'snippets' => array(
							'type' => 'checkbox',
							'choices' => array(
								'create_snippets' => 'create_snippets',
								'edit_snippets' => 'edit_snippets',
								'delete_snippets' => 'delete_snippets'
							),
							'value' => element('snippet_actions', $values)
						),
					)
				),
				array(
					'title' => 'global_variables',
					'desc' => 'global_variables_desc',
					'fields' => array(
						'global_variables' => array(
							'type' => 'checkbox',
							'choices' => array(
								'create_global_variables' => 'create_global_variables',
								'edit_global_variables' => 'edit_global_variables',
								'delete_global_variables' => 'delete_global_variables'
							),
							'value' => element('global_variable_actions', $values)
						),
					)
				),
			),
			'template_management' => array(
				array(
					'title' => 'allowed_actions',
					'desc' => 'allowed_actions_desc',
					'fields' => array(
						'template_management' => array(
							'type' => 'checkbox',
							'choices' => array(
								'manage_template_settings' => 'manage_template_settings',
								'create_templates' => 'create_templates',
								'edit_templates' => 'edit_templates',
								'delete_templates' => 'delete_templates'
							),
							'value' => element('template_management', $values)
						),
					)
				),
				array(
					'title' => 'allowed_template_groups',
					'desc' => 'allowed_template_groups_desc',
					'fields' => array(
						'snippets' => array(
							'type' => 'checkbox',
							'choices' => $template_groups,
							'value' => element('allowed_template_groups', $values)
						),
					)
				)
			),
			'addons' => array(
				array(
					'title' => 'manage_addons',
					'desc' => 'manage_addons_desc',
					'fields' => array(
						'manage_addons' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => element('manage_design_settings', $values)
						)
					)
				)
			),
			'addon_access' => array(
				array(
					'title' => 'addon_access',
					'desc' => 'addons_access_desc',
					'fields' => array()
				)
			),
			'tools' => array(
				array(
					'title' => 'access_tools',
					'desc' => 'access_tools_desc',
					'fields' => array(
						'access_tools' => array(
							'type' => 'checkbox',
							'choices' => array(
								'utilities' => 'utilities',
								'logs' => 'logs'
							),
							'value' => element('access_tools', $values)
						),
					)
				)
			),
			'settings' => array(
				array(
					'title' => 'access_settings',
					'desc' => 'access_settings_desc',
					'fields' => array(
						'access_settings' => array(
							'type' => 'checkbox',
							'choices' => array(
								'general_settings' => 'general_settings',
								'content_settings' => 'content_settings',
								'member_settings' => 'member_settings',
								'security_settings' => 'security_settings',
								'addon_settings' => 'addon_settings',
							),
							'value' => element('access_settings', $values)
						),
					)
				)
			)
		);

		ee('Alert')->makeInline('shared-form')
			->asWarning()
			->cannotClose()
			->withTitle(lang('access_privilege_warning'))
			->addToBody(lang('access_privilege_caution'), 'caution');

		ee()->form_validation->set_rules(array(
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->saveQuicklinks())
			{
				ee()->functions->redirect(cp_url($this->index_url, $this->query_string));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_save_working';
		ee()->cp->render('settings/form', $vars);
	}

	private function groupData($group)
	{
		$result = $group->getValues();

		// Site access checkbox group
		$result['site_access'] = array();

		if ($result['can_view_online_system'] == 'y')
		{
			$result['site_access'][] = 'can_view_online_system';
		}

		if ($result['can_view_offline_system'] == 'y')
		{
			$result['site_access'][] = 'can_view_offline_system';
		}

		// Include member in checkbox group
		$result['include_member_in'] = array();

		if ($result['include_in_authorlist'] == 'y')
		{
			$result['include_member_in'][] = 'include_in_authorlist';
		}

		if ($result['include_in_memberlist'] == 'y')
		{
			$result['include_member_in'][] = 'include_in_memberlist';
		}

		if ($result['include_in_mailinglists'] == 'y')
		{
			$result['include_member_in'][] = 'include_in_mailinglists';
		}

		// Comment moderation checkbox group
		$result['comment_actions'] = array();

		if ($result['can_edit_own_comments'] == 'y')
		{
			$result['comment_actions'][] = 'can_edit_own_comments';
		}

		if ($result['can_delete_own_comments'] == 'y')
		{
			$result['comment_actions'][] = 'can_delete_own_comments';
		}

		if ($result['can_edit_all_comments'] == 'y')
		{
			$result['comment_actions'][] = 'can_edit_all_comments';
		}

		if ($result['can_delete_all_comments'] == 'y')
		{
			$result['comment_actions'][] = 'can_delete_all_comments';
		}

		// Channel category checkbox group
		$result['category_actions'] = array();

		if ($result['can_edit_categories'] == 'y')
		{
			$result['site_access'][] = 'can_edit_categories';
		}

		if ($result['can_view_offline_system'] == 'y')
		{
			$result['site_access'][] = 'can_view_offline_system';
		}

		// Member actions checkbox group

		$result['member_actions'] = array();

		if ($result['can_view_offline_system'] == 'y')
		{
			$result['member_actions'][] = 'can_admin_members';
		}

		if ($result['can_view_offline_system'] == 'y')
		{
			$result['member_actions'][] = 'can_delete_members';
		}

		if ($result['can_view_offline_system'] == 'y')
		{
			$result['member_actions'][] = 'can_ban_users';
		}

		return $result;
	}

	private function defaults()
	{
		return $defaults;
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Members/Members.php */
