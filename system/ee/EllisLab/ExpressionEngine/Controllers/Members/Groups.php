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

		$group = ee()->input->get('group');
		$this->group = ee()->api->get('MemberGroup', array($group))->first();
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
							'value' => element('allowed_template_groups', $values)
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
		$ignore = array('allowed_template_groups', 'allowed_channels');

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
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Members/Members.php */
