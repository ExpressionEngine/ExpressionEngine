<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
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
	private $index_url;
	private $site_id;
	private $super_admin;
	private $group;
	private $query_string = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->base_url = new URL('members/groups', ee()->session->session_id());
		$this->site_id = (int) ee()->config->item('site_id');
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

		$table = ee('CP/Table', array(
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
			$data['pagination'] = ee('CP/Pagination', $total)
				->perPage($perpage)
				->currentPage($page)
				->render($base_url);
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

		$group = ee()->input->get('group');
		$this->group = ee()->api->get('MemberGroup', array($group))->first();
		$this->group_id = (int) $this->group->group_id;
		$this->query_string['group'] = $group;
		$this->base_url = cp_url('members/groups/edit/', $this->query_string);
		$current = $this->groupData($this->group);

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

		list($template_groups, $template_group_permissions) = $this->_setup_template_names($this->site_id, $this->group_id);
		list($addons, $addons_permissions) = $this->_setup_module_names($this->group_id);
		list($allowed_channels, $channel_permissions) = $this->_setup_channel_names($this->site_id, $this->group_id);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'group_name',
					'desc' => 'group_name_desc',
					'fields' => array(
						'group_title' => array(
							'type' => 'text',
							'value' => element('group_title', $values),
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'group_description',
					'desc' => 'group_description_desc',
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
						'is_locked' => array(
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
					'desc' => 'site_access_desc',
					'fields' => array(
						'website_access' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_view_online_system' => 'can_view_online_system',
								'can_view_offline_system' => 'can_view_offline_system'
							),
							'value' => element('site_access', $values)
						),
					)
				),
				array(
					'title' => 'can_view_profiles',
					'desc' => 'can_view_profiles_desc',
					'fields' => array(
						'can_view_profiles' => array(
							'type' => 'yes_no',
							'value' => element('can_view_profiles', $values)
						)
					)
				),
				array(
					'title' => 'can_send_email',
					'desc' => 'can_send_email_desc',
					'fields' => array(
						'can_send_email' => array(
							'type' => 'yes_no',
							'value' => element('can_send_email', $values)
						)
					)
				),
				array(
					'title' => 'can_delete_self',
					'desc' => 'can_delete_self_desc',
					'fields' => array(
						'can_delete_self' => array(
							'type' => 'yes_no',
							'value' => element('can_delete_self', $values)
						)
					)
				),
				array(
					'title' => 'mbr_delete_notify_emails',
					'desc' => 'mbr_delete_notify_emails_desc',
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
								'include_in_authorlist' => 'include_in_authorlist',
								'include_in_memberlist' => 'include_in_memberlist',
								'include_in_mailinglists' => 'include_in_mailinglists'
							),
							'value' => element('include_members_in', $values)
						),
					)
				)
			),
			'commenting' => array(
				array(
					'title' => 'can_post_comments',
					'desc' => 'can_post_comments_desc',
					'fields' => array(
						'can_post_comments' => array(
							'type' => 'yes_no',
							'value' => element('can_post_comments', $values)
						)
					)
				),
				array(
					'title' => 'exclude_from_moderation',
					'desc' => 'exclude_from_moderation_desc',
					'fields' => array(
						'exclude_from_moderation' => array(
							'type' => 'yes_no',
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
								'can_edit_own_comments' => 'can_edit_own_comments',
								'can_delete_own_comments' => 'can_delete_own_comments',
								'can_edit_all_comments' => 'can_edit_all_comments',
								'can_delete_all_comments' => 'can_delete_all_comments'
							),
							'value' => element('comment_actions', $values)
						),
					)
				)
			),
			'search' => array(
				array(
					'title' => 'can_search',
					'desc' => 'can_search_desc',
					'fields' => array(
						'can_search' => array(
							'type' => 'yes_no',
							'value' => element('can_search', $values)
						)
					)
				),
				array(
					'title' => 'search_flood_control',
					'desc' => 'search_flood_control_desc',
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
					'title' => 'can_send_private_messages',
					'desc' => 'can_send_private_messages_desc',
					'fields' => array(
						'can_send_private_messages' => array(
							'type' => 'yes_no',
							'value' => element('can_send_private_messages', $values)
						)
					)
				),
				array(
					'title' => 'prv_msg_send_limit',
					'desc' => 'prv_msg_send_limit_desc',
					'fields' => array(
						'prv_msg_send_limit' => array(
							'type' => 'text',
							'value' => element('prv_msg_send_limit', $values)
						)
					)
				),
				array(
					'title' => 'prv_msg_storage_limit',
					'desc' => 'prv_msg_storage_limit_desc',
					'fields' => array(
						'prv_msg_storage_limit' => array(
							'type' => 'text',
							'value' => element('prv_msg_storage_limit', $values)
						)
					)
				),
				array(
					'title' => 'can_attach_in_private_messages',
					'desc' => 'can_attach_in_private_messages_desc',
					'fields' => array(
						'can_attach_in_private_messages' => array(
							'type' => 'yes_no',
							'value' => element('can_attach_in_private_messages', $values)
						)
					)
				),
				array(
					'title' => 'can_send_bulletins',
					'desc' => 'can_send_bulletins_desc',
					'fields' => array(
						'can_send_bulletins' => array(
							'type' => 'yes_no',
							'value' => element('can_send_bulletins', $values)
						)
					)
				)
			),
			'control_panel' => array(
				array(
					'title' => 'can_access_cp',
					'desc' => 'can_access_cp_desc',
					'fields' => array(
						'can_access_cp' => array(
							'type' => 'yes_no',
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
							'type' => 'yes_no',
							'value' => element('can_admin_channels', $values)
						)
					)
				),
				array(
					'title' => 'category_actions',
					'desc' => 'category_actions_desc',
					'fields' => array(
						'category_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_edit_categories' => 'can_edit_categories',
								'can_delete_categories' => 'can_delete_categories'
							),
							'value' => element('category_actions', $values)
						),
					)
				)
			),
			'channel_entries_management' => array(
				array(
					'title' => 'channel_entry_actions',
					'desc' => 'channel_entry_actions_desc',
					'fields' => array(
						'channel_entry_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_delete_self_entries' => 'can_delete_self_entries',
								'can_edit_other_entries' => 'can_edit_other_entries',
								'can_delete_all_entries' => 'can_delete_all_entries',
								'can_assign_post_authors' => 'can_assign_post_authors'
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
					'title' => 'allowed_actions',
					'desc' => 'allowed_actions_desc',
					'fields' => array(
						'member_actions' => array(
							'type' => 'checkbox',
							'choices' => array(
								'can_admin_members' => 'can_admin_members',
								'can_delete_members' => 'can_delete_members',
								'can_ban_users' => 'can_ban_users',
							),
							'value' => element('member_actions', $values)
						)
					)
				)
			),
			'design' => array(
				array(
					'title' => 'can_admin_design',
					'desc' => 'can_admin_design_desc',
					'fields' => array(
						'can_admin_design' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => element('can_admin_design', $values)
						)
					)
				),
				array(
					'title' => 'can_admin_templates',
					'desc' => 'can_admin_templates_desc',
					'fields' => array(
						'can_admin_templates' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => element('can_admin_templates', $values)
						)
					)
				)
			),
			'template_management' => array(
				array(
					'title' => 'allowed_template_groups',
					'desc' => 'allowed_template_groups_desc',
					'fields' => array(
						'allowed_template_groups' => array(
							'type' => 'checkbox',
							'choices' => $template_groups,
							'value' => element('template_groups', $values)
						),
					)
				)
			),
			'addons' => array(
				array(
					'title' => 'can_admin_modules',
					'desc' => 'can_admin_modules_desc',
					'fields' => array(
						'can_admin_modules' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => element('can_admin_modules', $values)
						)
					)
				)
			),
			'addon_access' => array(
				array(
					'title' => 'addon_access',
					'desc' => 'addons_access_desc',
					'fields' => array(
						'addons_access' => array(
							'type' => 'checkbox',
							'choices' => $addons,
							'value' => element('addons_access', $values)
						),
					)
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
								'can_access_comm' => 'can_access_comm',
								'can_access_utilities' => 'can_access_utilities',
								'can_access_data' => 'can_access_data',
								'can_access_logs' => 'can_access_logs'
							),
							'value' => element('access_tools', $values)
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
			array(
				 'field'   => 'group_title',
				 'label'   => 'lang:group_title',
				 'rules'   => 'valid_xss_check'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->save($vars['sections']))
			{
				ee()->view->set_message('success', lang('member_group_updated'), lang('member_group_updated_desc'), TRUE);
			}

			ee()->functions->redirect(cp_url($this->index_url, $this->query_string));
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

		// Access tools checkbox group

		$result['access_tools'] = array();


		if ($result['can_access_logs'] == 'y')
		{
			$result['access_tools'][] = 'can_access_logs';
		}

		if ($result['can_access_data'] == 'y')
		{
			$result['access_tools'][] = 'can_access_data';
		}

		if ($result['can_access_utilities'] == 'y')
		{
			$result['access_tools'][] = 'can_access_utilities';
		}

		if ($result['can_access_comm'] == 'y')
		{
			$result['access_tools'][] = 'can_access_comm';
		}

		// Addons permissions
		list($addons, $addons_permissions) = $this->_setup_module_names($this->group_id);

		foreach ($addons_permissions as $permission => $value)
		{
			if ($value == 'y')
			{
				$result['addons_access'][] = $permission;
			}
		}

		list($template_groups, $template_group_permissions) = $this->_setup_template_names($this->site_id, $this->group_id);

		foreach ($template_group_permissions[$this->site_id] as $permission => $value)
		{
			if ($value == 'y')
			{
				$result['template_groups'][] = $permission;
			}
		}

		list($channels, $channel_permissions) = $this->_setup_channel_names($this->site_id, $this->group_id);

		foreach ($channel_permissions[$this->site_id] as $permission => $value)
		{
			if ($value == 'y')
			{
				$result['allowed_channels'][] = $permission;
			}
		}

		return $result;
	}

	private function defaults()
	{
		return $defaults;
	}

	private function save($sections)
	{
		$this->index_url = 'members/groups/edit';
		$allowed_channels = ee()->input->post('allowed_channels');
		$allowed_template_groups = ee()->input->post('allowed_template_groups');
		$allowed_addons = ee()->input->post('addons_access');
		$ignore = array('allowed_template_groups', 'allowed_channels', 'addons_access');

		// Set our various permissions if we're not editing the Super Admin
		if ($this->group->group_id !== 1)
		{
			$this->group->setAssignedModules(ee()->api->get('Module', $allowed_addons)->all());
			$this->group->setAssignedTemplateGroups(ee()->api->get('TemplateGroup', $allowed_template_groups)->all());
			$this->group->setAssignedChannels(ee()->api->get('Channel', $allowed_channels)->all());
		}

		if ( ! empty($this->group))
		{
			$group = $this->group;
		}
		else
		{
			$group = ee('Model')->make('MemberGroup');
		}

		foreach ($sections as $section)
		{
			foreach ($section as $item)
			{
				foreach ($item['fields'] as $field => $options)
				{
					if ( ! in_array($field, $ignore))
					{
						$submitted = ee()->input->post($field);
						$submitted = $submitted === FALSE ? array() : $submitted;

						if (is_array($submitted))
						{
							$choices = array_keys($options['choices']);
							$deselected = array_diff($choices, $submitted);

							foreach ($submitted as $item)
							{
								$group->$item = 'y';
							}

							foreach ($deselected as $item)
							{
								$group->$item = 'n';
							}
						}
						else
						{
							$group->$field = $submitted;
						}
					}
				}
			}
		}

		$group->save();
		$this->query_string['group'] = $group->group_id;

		return TRUE;
	}

	/**
	 * Setup Module Names
	 *
	 * Sets up module names for use in the edit_member_group data array.
	 *
	 * @param 	int 	member group id
	 * @return 	array 	array of module names and associated permissions.
	 */
	private function _setup_module_names($id)
	{
		// Load Module Language Files.
		ee()->load->library('addons');
		$mod_lang_files = ee()->addons->get_files('modules');

		foreach ($mod_lang_files as $m => $i)
		{
			ee()->lang->loadfile($m);
		}

		$module_names = array();
		$module_perms = array();
		$module_ids   = array();

		$modules = ee()->db->select('module_id, module_name')
							->where('has_cp_backend', 'y')
							->order_by('module_name')
							->get('modules');

		if ($id === 1)
		{
			// Super admins get it all
			foreach ($modules->result() as $row)
			{
				$name = lang(strtolower($row->module_name . '_module_name'));
				$name = ucwords(str_replace('_', ' ', $name));

				$module_names[$row->module_id] = $name;
				$module_perms[$row->module_id] = 'y';
			}

			$modules->free_result();

			return array($module_names, $module_perms);
		}

		$qry = ee()->db->select('module_id')
						->get_where('module_member_groups', array(
							'group_id' => $id
						));

		foreach ($qry->result() as $row)
		{
			$module_ids[$row->module_id] = TRUE;
		}

		$qry->free_result();

		foreach ($modules->result() as $row)
		{
			$name = lang(strtolower($row->module_name . '_module_name'));
			$name = ucwords(str_replace('_', ' ', $name));

			$module_names[$row->module_id] = $name;
			$module_perms[$row->module_id] = isset($module_ids[$row->module_id]) ? 'y' : 'n';
		}

		$modules->free_result();

		return array($module_names, $module_perms);
	}

	/**
	 * Setup channel names
	 *
	 * Gets channel names from the database and processes permissions,
	 * based on member group id
	 *
	 * @param 	int 	Site ID
	 * @param 	int 	Member Group ID
	 * @return 	array 	Array of channel names and associated permissions.
	 */
	private function _setup_channel_names($site_id, $id)
	{
		$channel_names = array();
		$channel_perms = array();
		$channel_ids   = array();

		$channels = $this->db->select('channel_id, site_id, channel_title')
			->where('site_id', $site_id)
			->order_by('channel_title')
			->get('channels');

		// Super Admins get everything
		if ($id === 1)
		{
			foreach ($channels->result() as $row)
			{
				$channel_names[$row->channel_id] = $row->channel_title;
				$channel_perms[$row->site_id][$row->channel_id] = 'y';
			}

			return array($channel_names, $channel_perms);
		}

		$qry = $this->db->select('channel_id')
						->get_where('channel_member_groups', array(
							'group_id'	=> $id
						));

		// Let's see what the members have access to.
		foreach ($qry->result() as $row)
		{
			$channel_ids[$row->channel_id] = TRUE;
		}

		$qry->free_result();

		foreach ($channels->result() as $row)
		{
			$channel_names[$row->channel_id] = $row->channel_title;
			$channel_perms[$row->site_id][$row->channel_id] = (isset($channel_ids[$row->channel_id])) ? 'y' : 'n';
		}

		$channels->free_result();

		return array($channel_names, $channel_perms);
	}

	/**
	 * Setup template names
	 *
	 * Assembles template names from the database for use in the group_data array
	 *
	 * @param 	int 	Site ID
	 * @param 	int 	Member group ID used for permissions checking
	 * @return 	array 	Array of template names and associated permissions
	 */
	private function _setup_template_names($site_id, $id)
	{
		$template_names = array();
		$template_perms = array();
		$template_ids   = array();

		$templates = $this->db->select('group_id, group_name, site_id')
			->where('site_id', $site_id)
			->order_by('group_name')
			->get('template_groups');

		if ($id === 1)
		{
			foreach ($templates->result() as $row)
			{
				$template_names[$row->group_id] = $row->group_name;
				$template_perms[$row->site_id][$row->group_id] = 'y';
			}

			$templates->free_result();

			return array($template_names, $template_perms);
		}

		$qry = $this->db->select('template_group_id')
						->get_where('template_member_groups', array(
							'group_id' => $id
						));

		foreach ($qry->result() as $row)
		{
			$template_ids[$row->template_group_id] = TRUE;
		}

		$qry->free_result();

		foreach ($templates->result() as $row)
		{
			$template_names[$row->group_id] = $row->group_name;
			$template_perms[$row->site_id][$row->group_id] = isset($template_ids[$row->group_id]) ? 'y' : 'n';
		}

		$templates->free_result();

		return array($template_names, $template_perms);
	}

}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Members/Members.php */
