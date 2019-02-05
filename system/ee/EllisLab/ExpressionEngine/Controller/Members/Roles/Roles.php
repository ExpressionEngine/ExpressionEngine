<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Roles;

use EllisLab\ExpressionEngine\Controller\Members\Roles\AbstractRoles as AbstractRolesController;
use EllisLab\ExpressionEngine\Model\Role\Role;

/**
 * Members\Roles\Roles Controller
 */
class Roles extends AbstractRolesController {

	public function index()
	{
		$group_id = ee('Request')->get('group_id');

		if ($group_id)
		{
			$base_url = ee('CP/URL')->make('roles', ['group_id' => $group_id]);
		}
		else
		{
			$base_url = ee('CP/URL')->make('roles');
		}

		if (ee('Request')->post('bulk_action') == 'remove')
		{
			$this->remove(ee('Request')->post('selection'));
			ee()->functions->redirect($base_url);
		}

		$this->generateSidebar($group_id);

		$vars['create_url'] = $group_id
			? ee('CP/URL')->make('roles/create/'.$group_id)
			: ee('CP/URL')->make('roles/create');
		$vars['base_url'] = $base_url;

		$data = array();

		$role_id = ee()->session->flashdata('role_id');

		// Set up filters
		$group_ids = ee('Model')->get('RoleGroup')
			->order('name')
			->all()
			->getDictionary('group_id', 'name');

		$filters = ee('CP/Filter');
		$group_filter = $filters->make('group_id', 'group_filter', $group_ids);
		$group_filter->setPlaceholder(lang('all'));
		$group_filter->disableCustomValue();

		$page = ee('Request')->get('page') ?: 1;
		$per_page = 10;

		$filters->add($group_filter);

		$filter_values = $filters->values();
		$search = isset($filter_values['filter_by_keyword']) ? $filter_values['filter_by_keyword'] : NULL;

		$total_roles = 0;

		$group = $group_id && $group_id != 'all'
			? ee('Model')->get('RoleGroup', $group_id)->first()
			: NULL;

		// Are we showing a specific group? If so, we need to apply filtering differently
		// because we are acting on a collection instead of a query builder
		if ($group)
		{
			$roles = $group->Roles->sortBy('name')->asArray();

			if ($search)
			{
				$roles = array_filter($roles, function($role) use ($search) {
					return strpos(
						strtolower($role->name),
						strtolower($search)
					) !== FALSE;
				});
			}

			$total_roles = count($roles);
		}
		else
		{
			$roles = ee('Model')->get('Role');

			if ($search)
			{
				$roles->search(['name'], $search);
			}

			$total_roles = $roles->count();
		}

		$filters->add('Keyword')
			->add('Perpage', $total_roles, 'all_roles', TRUE);

		$filter_values = $filters->values();
		$vars['base_url']->addQueryStringVariables($filter_values);
		$per_page = $filter_values['perpage'];

		if ($group)
		{
			$roles = array_slice($roles, (($page - 1) * $per_page), $per_page);
		}
		else
		{
			$roles = $roles->limit($per_page)
				->offset(($page - 1) * $per_page)
				->order('name')
				->all();
		}

		// Only show filters if there is data to filter or we are currently filtered
		if ($group_id OR ! empty($roles))
		{
			$vars['filters'] = $filters->render(ee('CP/URL')->make('members/roles'));
		}

		foreach ($roles as $role)
		{
			$edit_url = ee('CP/URL')->make('members/roles/edit/' . $role->getId());

			$data[] = [
				'id' => $role->getId(),
				'label' => $role->name,
				'href' => $edit_url,
				'selected' => ($role_id && $role->getId() == $role_id),
				'toolbar_items' => ee('Permission')->can('edit_roles') ? [
					'edit' => [
						'href' => $edit_url,
						'title' => lang('edit')
					]
				] : NULL,
				'selection' => ee('Permission')->can('delete_roles') ? [
					'name' => 'selection[]',
					'value' => $role->getId(),
					'data' => [
						'confirm' => lang('role') . ': <b>' . ee('Format')->make('Text', $role->name)->convertToEntities() . '</b>'
					]
				] : NULL
			];
		}

		if (ee('Permission')->can('delete_roles'))
		{
			ee()->javascript->set_global('lang.remove_confirm', lang('role') . ': <b>### ' . lang('roles') . '</b>');
			ee()->cp->add_js_script(array(
				'file' => array(
					'cp/confirm_remove',
				),
			));
		}

		$vars['pagination'] = ee('CP/Pagination', $total_roles)
			->perPage($per_page)
			->currentPage($page)
			->render($vars['base_url']);

		$vars['cp_page_title'] = $group
			? $group->name . '&mdash;' . lang('roles' )
			: lang('all_roles');
		$vars['roles'] = $data;
		$vars['no_results'] = ['text' => sprintf(lang('no_found'), lang('roles')), 'href' => $vars['create_url']];

		ee()->cp->render('members/roles/index', $vars);
	}

	public function create($group_id = NULL)
	{
		if ( ! ee('Permission')->can('create_roles'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if (ee('Request')->post('group_id'))
		{
			$group_id = ee('Request')->post('group_id');
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('members/roles')->compile() => lang('roles_manager')
		);

		$this->generateSidebar($group_id);

		$errors = NULL;
		$role = ee('Model')->make('Role');

		if ( ! empty($_POST))
		{
			$role = $this->setWithPost($role);
			$result = $role->validate();

			if (isset($_POST['ee_fv_role']) && $response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$role->save();

				if ($group_id)
				{
					$role_group = ee('Model')->get('RoleGroup', $group_id)->first();
					if ($role_group)
					{
						$role_group->Roles->getAssociation()->add($role);
						$role_group->save();
					}
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_role_success'))
					->addToBody(sprintf(lang('create_role_success_desc'), $role->name))
					->defer();

				if (AJAX_REQUEST)
				{
					return ['saveId' => $role->getId()];
				}

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					$return = (empty($group_id)) ? '' : '/'.$group_id;
					ee()->functions->redirect(ee('CP/URL')->make('members/roles/create'.$return));
				}
				elseif (ee('Request')->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('members/roles'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('members/roles/edit/'.$role->getId()));
				}
			}
			else
			{
				$errors = $result;

				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('create_role_error'))
					->addToBody(lang('create_role_error_desc'))
					->now();
			}
		}

		$vars = array(
			'errors' => $errors,
			'ajax_validate' => TRUE,
			'base_url' => $group_id
				? ee('CP/URL')->make('members/roles/create/'.$group_id)
				: ee('CP/URL')->make('members/roles/create'),
			'sections' => [],
			'tabs' => $this->getTabs($role, $errors),
			'buttons' => [
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
			],
			'form_hidden' => array(
				'role_id' => NULL
			),
		);

		if (AJAX_REQUEST)
		{
			unset($vars['buttons'][2]);
		}

		ee()->view->cp_page_title = lang('create_new_role');

		ee()->view->extra_alerts = array('search-reindex'); // for Save & New

		if (AJAX_REQUEST)
		{
			return ee()->cp->render('_shared/form', $vars);
		}

		ee()->cp->add_js_script('plugin', 'ee_url_title');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($id)
	{
		if ( ! ee('Permission')->can('edit_roles'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$role = ee('Model')->get('Role', $id)
			->first();

		if ( ! $role)
		{
			show_404();
		}

		$role_groups = $role->RoleGroups;
		$active_groups = $role_groups->pluck('group_id');
		$this->generateSidebar($active_groups);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('roles')->compile() => lang('roles_manager'),
		);

		$errors = NULL;

		if ( ! empty($_POST))
		{
			$role = $this->setWithPost($role);
			$result = $role->validate();

			if ($response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$role->save();

				if (ee('Request')->post('update_formatting') == 'y')
				{
					ee()->db->where('role_ft_' . $role->role_id . ' IS NOT NULL', NULL, FALSE);
					ee()->db->update(
						$role->getDataStorageTable(),
						array('role_ft_'.$role->role_id => $role->role_fmt)
					);
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('edit_role_success'))
					->addToBody(sprintf(lang('edit_role_success_desc'), $role->name))
					->defer();

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('members/roles/create'));
				}
				elseif (ee('Request')->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('members/roles'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('members/roles/edit/'.$role->getId()));
				}
			}
			else
			{
				$errors = $result;

				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('edit_role_error'))
					->addToBody(lang('edit_role_error_desc'))
					->now();
			}
		}

		$vars = array(
			'errors' => $errors,
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('members/roles/edit/' . $id),
			'sections' => [],
			'tabs' => $this->getTabs($role, $errors),
			'buttons' => [
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
			],
			'form_hidden' => array(
				'role_id' => $id,
			),
		);

		ee()->view->cp_page_title = lang('edit_role');
		ee()->view->extra_alerts = array('search-reindex');

		ee()->cp->render('settings/form', $vars);
	}

	private function setWithPost(Role $role)
	{
		$role->set($_POST);

		return $role;
	}

	private function getTabs(Role $role, $errors)
	{
		ee()->cp->add_js_script(array(
			'file' => array('cp/form_group'),
		));

		return [
			'role'        => $this->renderRoleTab($role, $errors),
			'site_access' => $this->renderSiteAccessTab($role, $errors),
			'cp_access'   => $this->renderCPAccessTab($role, $errors),
			// 'fields'         => '',
		];
	}

	private function renderRoleTab(Role $role, $errors)
	{
		$settings = $role->RoleSettings->indexBy('site_id');
		$site_id = ee()->config->item('site_id');

		$settings = (isset($settings[$site_id])) ? $settings[$site_id] : ee('Model')->make('RoleSetting', ['site_id' => $site_id]);

		$role_groups = ee('Model')->get('RoleGroup')
			->fields('group_id', 'name')
			->order('name')
			->all()
			->getDictionary('group_id', 'name');

		$section = [
			[
				'title' => 'name',
				'fields' => [
					'name' => [
						'type' => 'text',
						'required' => TRUE,
						'value' => $role->name
					]
				]
			],
			[
				'title' => 'description',
				'fields' => [
					'description' => [
						'type' => 'textarea',
						'value' => $role->description
					]
				]
			],
			[
				'title' => 'security_lock',
				'desc' => 'lock_description',
				'fields' => [
					'is_locked' => [
						'type' => 'yes_no',
						'value' => $settings->is_locked,
					]
				]
			],
			[
				'title' => 'role_groups',
				'desc' => 'role_groups_desc',
				'fields' => [
					'role_groups' => [
						'type' => 'checkbox',
						'choices' => $role_groups,
						'value' => $role->RoleGroups->pluck('group_id')
					]
				]
			]
		];

		return ee('View')->make('_shared/form/section')
				->render(array('name' => NULL, 'settings' => $section, 'errors' => $errors));
	}

	private function renderSiteAccessTab(Role $role, $errors)
	{
		$settings = $role->RoleSettings->indexBy('site_id');
		$site_id = ee()->config->item('site_id');

		$settings = (isset($settings[$site_id])) ? $settings[$site_id] : ee('Model')->make('RoleSetting', ['site_id' => $site_id]);

		$website_access_choices = [
			'can_view_online_system'  => lang('can_view_online_system'),
			'can_view_offline_system' => lang('can_view_offline_system')
		];
		$website_access_value = [];
		foreach (array_keys($website_access_choices) as $perm)
		{
			if ($role->has($perm))
			{
				$website_access_value[] = $perm;
			}
		}

		$include_members_in_choices = [
			'include_in_authorlist' => lang('include_in_authorlist'),
			'include_in_memberlist' => lang('include_in_memberlist'),
		];
		$include_members_in_value = [];
		foreach (array_keys($include_members_in_choices) as $key)
		{
			if ($settings->$key)
			{
				$include_members_in_value[] = $key;
			}
		}

		$comment_actions_choices = [
			'can_moderate_comments'   => lang('can_moderate_comments'),
			'can_edit_own_comments'   => lang('can_edit_own_comments'),
			'can_delete_own_comments' => lang('can_delete_own_comments'),
			'can_edit_all_comments'   => lang('can_edit_all_comments'),
			'can_delete_all_comments' => lang('can_delete_all_comments')
		];
		$comment_actions_value = [];
		foreach (array_keys($comment_actions_choices) as $perm)
		{
			if ($role->has($perm))
			{
				$comment_actions_value[] = $perm;
			}
		}

		$sections = [
			[
				[
					'title' => 'site_access',
					'desc' => 'site_access_desc',
					'fields' => [
						'website_access' => [
							'type' => 'checkbox',
							'choices' => $website_access_choices,
							'value' => $website_access_value,
							'encode' => FALSE
						]
					]
				],
				[
					'title' => 'can_view_profiles',
					'desc' => 'can_view_profiles_desc',
					'fields' => [
						'can_view_profiles' => [
							'type' => 'yes_no',
							'value' => $role->has('can_view_profiles')
						]
					]
				],
				[
					'title' => 'can_delete_self',
					'desc' => 'can_delete_self_desc',
					'fields' => [
						'can_delete_self' => [
							'type' => 'yes_no',
							'value' => $role->has('can_delete_self')
						]
					]
				],
				[
					'title' => 'mbr_delete_notify_emails',
					'desc' => 'mbr_delete_notify_emails_desc',
					'fields' => [
						'mbr_delete_notify_emails' => [
							'type' => 'text',
							'value' => $settings->mbr_delete_notify_emails
						]
					]
				],
				[
					'title' => 'include_members_in',
					'desc' => 'include_members_in_desc',
					'fields' => [
						'include_members_in' => [
							'type' => 'checkbox',
							'choices' => $include_members_in_choices,
							'value' => $include_members_in_value
						]
					]
				]
			],
			'commenting' => [
				[
					'title' => 'can_post_comments',
					'desc' => 'can_post_comments_desc',
					'fields' => [
						'can_post_comments' => [
							'type' => 'yes_no',
							'value' => $role->has('can_post_comments'),
							'group_toggle' => [
								'y' => 'can_post_comments'
							]
						]
					]
				],
				[
					'title' => 'exclude_from_moderation',
					'desc' => sprintf(lang('exclude_from_moderation_desc'), ee('CP/URL', 'settings/comments')),
					'group' => 'can_post_comments',
					'fields' => [
						'exclude_from_moderation' => [
							'type' => 'yes_no',
							'value' => $settings->exclude_from_moderation,
						]
					]
				],
				[
					'title' => 'comment_actions',
					'desc' => 'comment_actions_desc',
					'caution' => TRUE,
					'fields' => [
						'comment_actions' => [
							'type' => 'checkbox',
							'choices' => $comment_actions_choices,
							'value' => $comment_actions_value,
						]
					]
				],
			],
			'search' => [
				[
					'title' => 'can_search',
					'desc' => 'can_search_desc',
					'fields' => [
						'can_search' => [
							'type' => 'yes_no',
							'value' => $role->has('can_search'),
							'group_toggle' => [
								'y' => 'can_search'
							]
						]
					]
				],
				[
					'title' => 'search_flood_control',
					'desc' => 'search_flood_control_desc',
					'group' => 'can_search',
					'fields' => [
						'search_flood_control' => [
							'type' => 'text',
							'value' => $settings->search_flood_control,
						]
					]
				],
			],
			'personal_messaging' => [
				[
					'title' => 'can_send_private_messages',
					'desc' => 'can_send_private_messages_desc',
					'fields' => [
						'can_send_private_messages' => [
							'type' => 'yes_no',
							'value' => $role->has('can_send_private_messages'),
							'group_toggle' => [
								'y' => 'can_access_pms'
							]
						]
					]
				],
				[
					'title' => 'prv_msg_send_limit',
					'desc' => 'prv_msg_send_limit_desc',
					'group' => 'can_access_pms',
					'fields' => [
						'prv_msg_send_limit' => [
							'type' => 'text',
							'value' => $settings->prv_msg_send_limit,
						]
					]
				],
				[
					'title' => 'prv_msg_storage_limit',
					'desc' => 'prv_msg_storage_limit_desc',
					'group' => 'can_access_pms',
					'fields' => [
						'prv_msg_storage_limit' => [
							'type' => 'text',
							'value' => $settings->prv_msg_storage_limit,
						]
					]
				],
				[
					'title' => 'can_attach_in_private_messages',
					'desc' => 'can_attach_in_private_messages_desc',
					'group' => 'can_access_pms',
					'fields' => [
						'can_attach_in_private_messages' => [
							'type' => 'yes_no',
							'value' => $role->has('can_attach_in_private_messages'),
						]
					]
				],
				[
					'title' => 'can_send_bulletins',
					'desc' => 'can_send_bulletins_desc',
					'group' => 'can_access_pms',
					'fields' => [
						'can_send_bulletins' => [
							'type' => 'yes_no',
							'value' => $role->has('can_send_bulletins'),
						]
					]
				],
			]
		];

		$html = '';

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	private function renderCPAccessTab(Role $role, $errors)
	{
		$settings = $role->RoleSettings->indexBy('site_id');
		$site_id = ee()->config->item('site_id');

		$settings = (isset($settings[$site_id])) ? $settings[$site_id] : ee('Model')->make('RoleSetting', ['site_id' => $site_id]);

		$default_homepage_choices = array(
			'overview' => lang('cp_overview').' &mdash; <i>'.lang('default').'</i>',
			'entries_edit' => lang('edit_listing')
		);

		$allowed_channels = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->getDictionary('channel_id', 'channel_title');

		if (count($allowed_channels))
		{
			$default_homepage_choices['publish_form'] = lang('publish_form').' &mdash; '.
				form_dropdown('cp_homepage_channel', $allowed_channels, $settings->cp_homepage_channel);
		}

		$default_homepage_choices['custom'] = lang('custom_uri');
		$default_homepage_value = [];

		$addons = ee('Model')->get('Module')
			->fields('module_id', 'module_name')
			->filter('module_name', 'NOT IN', array('Channel', 'Comment', 'Member', 'File', 'Filepicker')) // @TODO This REALLY needs abstracting.
			->all()
			->filter(function($addon) {
				$provision = ee('Addon')->get(strtolower($addon->module_name));

				if ( ! $provision)
				{
					return FALSE;
				}

				$addon->module_name = $provision->getName();
				return TRUE;
			})
			->getDictionary('module_id', 'module_name');

		$permissions = [
			'choices' => [
				'footer' => [
					'can_access_footer_report_bug' => lang('report_bug'),
					'can_access_footer_new_ticket' => lang('new_ticket'),
					'can_access_footer_user_guide' => lang('user_guide'),
				],
				'channel' => [
					'can_create_channels' => lang('create_channels'),
					'can_edit_channels'   => lang('edit_channels'),
					'can_delete_channels' => lang('delete_channels')
				],
				'channel_field' => [
					'can_create_channel_fields' => lang('create_channel_fields'),
					'can_edit_channel_fields'   => lang('edit_channel_fields'),
					'can_delete_channel_fields' => lang('delete_channel_fields')
				],
				'channel_category' => [
					'can_create_categories' => lang('create_categories'),
					'can_edit_categories'   => lang('edit_categories'),
					'can_delete_categories' => lang('delete_categories')
				],
				'channel_status' => [
					'can_create_statuses' => lang('create_statuses'),
					'can_edit_statuses'   => lang('edit_statuses'),
					'can_delete_statuses' => lang('delete_statuses')
				],
				'file_upload_directories' => [
					'can_create_upload_directories' => lang('create_upload_directories'),
					'can_edit_upload_directories'   => lang('edit_upload_directories'),
					'can_delete_upload_directories' => lang('delete_upload_directories'),
				],
				'files' => [
					'can_upload_new_files' => lang('upload_new_files'),
					'can_edit_files'       => lang('edit_files'),
					'can_delete_files'     => lang('delete_files'),
				],
				'role_actions' => [
					'can_create_roles' => lang('create_roles'),
					'can_edit_roles'   => lang('edit_roles'),
					'can_delete_roles' => lang('delete_roles'),
				],
				'member_actions' => [
					'can_create_members'     => lang('create_members'),
					'can_edit_members'       => lang('edit_members'),
					'can_delete_members'     => lang('can_delete_members'),
					'can_ban_users'          => lang('can_ban_users'),
					'can_email_from_profile' => lang('can_email_from_profile'),
					'can_edit_html_buttons'  => lang('can_edit_html_buttons')
				],
				'template_group' => [
					'can_create_template_groups' => lang('create_template_groups'),
					'can_edit_template_groups'   => lang('edit_template_groups'),
					'can_delete_template_groups' => lang('delete_template_groups'),
				],
				'template_partials' => [
					'can_create_template_partials' => lang('create_template_partials'),
					'can_edit_template_partials'   => lang('edit_template_partials'),
					'can_delete_template_partials' => lang('delete_template_partials'),
				],
				'template_variables' => [
					'can_create_template_variables' => lang('create_template_variables'),
					'can_edit_template_variables'   => lang('edit_template_variables'),
					'can_delete_template_variables' => lang('delete_template_variables'),
				],
				'rte_toolsets' => [
					'can_upload_new_toolsets' => lang('upload_new_toolsets'),
					'can_edit_toolsets'       => lang('edit_toolsets'),
					'can_delete_toolsets'     => lang('delete_toolsets')
				],
				'access_tools' => [
					'can_access_comm' => [
						'label' => lang('can_access_communicate'),
						'instructions' => lang('utility'),
						'children' => [
							'can_email_roles' => lang('can_email_roles'),
							'can_send_cached_email' => lang('can_send_cached_email'),
						]
					],
					'can_access_translate' => [
						'label' => lang('can_access_translate'),
						'instructions' => lang('utility')
					],
					'can_access_import' => [
						'label' => lang('can_access_import'),
						'instructions' => lang('utility')
					],
					'can_access_sql_manager' => [
						'label' => lang('can_access_sql'),
						'instructions' => lang('utility')
					],
					'can_access_data' => [
						'label' => lang('can_access_data'),
						'instructions' => lang('utility')
					]
				],
			],
			'values' => []
		];

		foreach ($permissions['choices'] as $group => $choices)
		{
			$permissions['values'][$group] = [];
			foreach ($choices as $perm => $data)
			{
				if ($role->has($perm))
				{
					$permissions['values'][$group][] = $perm;
				}

				// Nested choices
				if (is_array($data) && isset($data['children']))
				{
					foreach (array_keys($data['children']) as $child_perm)
					{
						if ($role->has($child_perm))
						{
							$permissions['values'][$group][] = $child_perm;
						}
					}
				}
			}
		}

		$sections = [
			[
				[
					'title' => 'can_access_cp',
					'desc' => 'can_access_cp_desc',
					'caution' => TRUE,
					'fields' => [
						'can_access_cp' => [
							'type' => 'yes_no',
							'value' => $role->has('can_access_cp'),
							'group_toggle' => [
								'y' => 'can_access_cp'
							]
						]
					]
				],
				[
					'title' => 'default_cp_homepage',
					'desc' => 'default_cp_homepage_desc',
					'group' => 'can_access_cp',
					'fields' => [
						'cp_homepage' => [
							'type' => 'radio',
							'choices' => $default_homepage_choices,
							'value' => $default_homepage_value,
							'encode' => FALSE
						],
						'cp_homepage_custom' => [
							'type' => 'text',
							'value' => $settings->cp_homepage_custom,
						]
					]
				],
				[
					'title' => 'footer_helper_links',
					'desc' => 'footer_helper_links_desc',
					'group' => 'can_access_cp',
					'fields' => [
						'footer_helper_links' => [
							'type' => 'checkbox',
							'choices' => $permissions['choices']['footer'],
							'value' => $permissions['values']['footer'],
						]
					]
				],
				[
					'title'  => 'homepage_news',
					'desc'   => 'homepage_news_desc',
					'group'  => 'can_access_cp',
					'fields' => [
						'can_view_homepage_news' => [
							'type' => 'yes_no',
							'value' => $role->has('can_view_homepage_news'),
						]
					]
				],
			],
			'channels' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_admin_channels',
						'desc' => 'can_admin_channels_desc',
						'caution' => TRUE,
						'fields' => [
							'can_admin_channels' => [
								'type' => 'yes_no',
								'value' => $role->has('can_admin_channels'),
								'group_toggle' => [
									'y' => 'can_admin_channels'
								]
							]
						]
					],
					[
						'title' => 'channels',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_admin_channels',
						'fields' => [
							'channel_permissions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['channel'],
								'value' => $permissions['values']['channel'],
							]
						]
					],
					[
						'title' => 'channel_fields',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_admin_channels',
						'fields' => [
							'channel_field_permissions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['channel_field'],
								'value' => $permissions['values']['channel_field'],
							]
						]
					],
					[
						'title' => 'channel_categories',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_admin_channels',
						'fields' => [
							'channel_category_permissions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['channel_category'],
								'value' => $permissions['values']['channel_category'],
							]
						]
					],
					[
						'title' => 'channel_statuses',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_admin_channels',
						'fields' => [
							'channel_status_permissions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['channel_status'],
								'value' => $permissions['values']['channel_status'],
							]
						]
					],
				]
			],
			'channel_entries_management' => [

			],
			'file_manager' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_access_file_manager',
						'desc' => 'file_manager_desc',
						'fields' => [
							'can_access_files' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_files'),
								'group_toggle' => [
									'y' => 'can_access_files'
								]
							]
						]
					],
					[
						'title' => 'file_upload_directories',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_files',
						'fields' => [
							'file_upload_directories' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['file_upload_directories'],
								'value' => $permissions['values']['file_upload_directories'],
							]
						]
					],
					[
						'title' => 'files',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_files',
						'fields' => [
							'files' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['files'],
								'value' => $permissions['values']['files'],
							]
						]
					],
				]
			],
			'members' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_access_members',
						'desc' => 'can_access_members_desc',
						'fields' => [
							'can_access_members' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_members'),
								'group_toggle' => [
									'y' => 'can_access_members'
								]
							]
						]
					],
					[
						'title' => 'can_admin_roles',
						'desc' => 'can_admin_roles_desc',
						'caution' => TRUE,
						'group' => 'can_access_members',
						'fields' => [
							'can_admin_roles' => [
								'type' => 'yes_no',
								'value' => $role->has('can_admin_roles'),
							]
						]
					],
					[
						'title' => 'roles',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_members',
						'caution' => TRUE,
						'fields' => [
							'role_actions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['role_actions'],
								'value' => $permissions['values']['role_actions'],
							]
						]
					],
					[
						'title' => 'members',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_members',
						'caution' => TRUE,
						'fields' => [
							'member_actions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['member_actions'],
								'value' => $permissions['values']['member_actions'],
								'encode' => FALSE
							]
						]
					],
				]
			],
			'template_manager' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_access_design',
						'desc' => 'can_access_design_desc',
						'fields' => [
							'can_access_design' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_design'),
								'group_toggle' => [
									'y' => 'can_access_design'
								]
							]
						]
					],
					[
						'title' => 'can_admin_design',
						'desc' => 'can_admin_design_desc',
						'group' => 'can_access_design',
						'caution' => TRUE,
						'fields' => [
							'can_admin_design' => [
								'type' => 'yes_no',
								'value' => $role->has('can_admin_design'),
							]
						]
					],
					[
						'title' => 'template_groups',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_design',
						'caution' => TRUE,
						'fields' => [
							'template_group_permissions' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['template_group'],
								'value' => $permissions['values']['template_group']
							]
						]
					],
					[
						'title' => 'template_partials',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_design',
						'caution' => TRUE,
						'fields' => [
							'template_partials' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['template_partials'],
								'value' => $permissions['values']['template_partials']
							]
						]
					],
					[
						'title' => 'template_variables',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_design',
						'caution' => TRUE,
						'fields' => [
							'template_variables' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['template_variables'],
								'value' => $permissions['values']['template_variables']
							]
						]
					],
				]
			],
			'addons' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_access_addons',
						'desc' => 'can_access_addons_desc',
						'fields' => [
							'can_access_addons' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_addons'),
								'group_toggle' => [
									'y' => 'can_access_addons'
								]
							]
						]
					],
					[
						'title' => 'can_admin_addons',
						'desc' => 'can_admin_addons_desc',
						'group' => 'can_access_addons',
						'caution' => TRUE,
						'fields' => [
							'can_admin_addons' => [
								'type' => 'yes_no',
								'value' => $role->has('can_admin_addons'),
							]
						]
					],
					[
						'title' => 'addons_access',
						'desc' => 'addons_access_desc',
						'group' => 'can_access_addons',
						'caution' => TRUE,
						'fields' => [
							'addons_access' => [
								'type' => 'checkbox',
								'choices' => $addons,
								'value' => $role->AssignedModules->pluck('module_id'),
								'no_results' => [
									'text' => sprintf(lang('no_found'), lang('addons'))
								]
							]
						]
					],
					[
						'title' => 'rte_toolsets',
						'desc' => 'allowed_actions_desc',
						'group' => 'can_access_addons',
						'fields' => [
							'rte_toolsets' => [
								'type' => 'checkbox',
								'choices' => $permissions['choices']['rte_toolsets'],
								'value' => $permissions['values']['rte_toolsets']
							]
						]
					],
				]
			],
			'tools_utilities' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'access_utilities',
						'desc' => 'access_utilities_desc',
						'fields' => [
							'can_access_utilities' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_utilities'),
								'group_toggle' => [
									'y' => 'can_access_utilities'
								]
							]
						]
					],
					[
						'title' => 'utilities_section',
						'desc' => 'utilities_section_desc',
						'group' => 'can_access_utilities',
						'caution' => TRUE,
						'fields' => [
							'access_tools' => [
								'type' => 'checkbox',
								'nested' => TRUE,
								'auto_select_parents' => TRUE,
								'choices' => $permissions['choices']['access_tools'],
								'value' => $permissions['values']['access_tools']
							]
						]
					],
				]
			],
			'logs' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_access_logs',
						'desc' => 'can_access_logs_desc',
						'fields' => [
							'can_access_logs' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_logs'),
							]
						]
					]
				]
			],
			'settings' => [
				'group' => 'can_access_cp',
				'settings' => [
					[
						'title' => 'can_access_sys_prefs',
						'desc' => 'can_access_sys_prefs_desc',
						'caution' => TRUE,
						'fields' => [
							'can_access_sys_prefs' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_sys_prefs'),
								'group_toggle' => [
									'y' => 'can_access_sys_prefs'
								]
							]
						]
					],
					[
						'title' => 'can_access_security_settings',
						'desc' => 'can_access_security_settings_desc',
						'group' => 'can_access_sys_prefs',
						'caution' => TRUE,
						'fields' => [
							'can_access_security_settings' => [
								'type' => 'yes_no',
								'value' => $role->has('can_access_security_settings'),
							]
						]
					],
					[
						'title' => 'can_manage_consents',
						'desc' => 'can_manage_consents_desc',
						'group' => 'can_access_sys_prefs',
						'caution' => TRUE,
						'fields' => [
							'can_manage_consents' => [
								'type' => 'yes_no',
								'value' => $role->has('can_manage_consents'),
							]
						]
					]
				]
			]
		];

		$html = '';

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	private function form(Role $role = NULL)
	{
		if ( ! $role)
		{
			$role = ee('Model')->make('Role');
		}

		$sections = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'roles' => array(
						'role_type' => array(
							'type' => 'dropdown',
							'choices' => $roletype_choices,
							'group_toggle' => $roletypes->getDictionary('name', 'name'),
							'value' => $role->role_type,
							'no_results' => ['text' => sprintf(lang('no_found'), lang('roletypes'))]
						)
					)
				),
				array(
					'title' => 'name',
					'roles' => array(
						'name' => array(
							'type' => 'text',
							'value' => $role->name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'alphadash_desc',
					'roles' => array(
						'role_name' => array(
							'type' => 'text',
							'value' => $role->role_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'instructions',
					'desc' => 'instructions_desc',
					'roles' => array(
						'role_instructions' => array(
							'type' => 'textarea',
							'value' => $role->role_instructions,
						)
					)
				),
				array(
					'title' => 'require_role',
					'desc' => 'require_role_desc',
					'roles' => array(
						'role_required' => array(
							'type' => 'yes_no',
							'value' => $role->role_required,
						)
					)
				),
				array(
					'title' => 'include_in_search',
					'desc' => 'include_in_search_desc',
					'roles' => array(
						'role_search' => array(
							'type' => 'yes_no',
							'value' => $role->role_search,
						)
					)
				),
				array(
					'title' => 'hide_role',
					'desc' => 'hide_role_desc',
					'roles' => array(
						'role_is_hidden' => array(
							'type' => 'yes_no',
							'value' => $role->role_is_hidden,
						)
					)
				),
			),
		);

		$role_options = $role->getSettingsForm();
		if (is_array($role_options) && ! empty($role_options))
		{
			$sections = array_merge($sections, $role_options);
		}

		foreach ($roletypes as $roletype)
		{
			if ($roletype->name == $role->role_type)
			{
				continue;
			}

			// If editing an option role, populate the dummy roletype with the
			// same settings to make switching between the different types easy
			if ( ! $role->isNew() &&
				in_array(
					$roletype->name,
					array('checkboxes', 'multi_select', 'radio', 'select')
				))
			{
				$dummy_role = clone $role;
			}
			else
			{
				$dummy_role = ee('Model')->make('Role');
			}
			$dummy_role->role_type = $roletype->name;
			$role_options = $dummy_role->getSettingsForm();

			if (is_array($role_options) && ! empty($role_options))
			{
				$sections = array_merge($sections, $role_options);
			}
		}

		ee()->javascript->output('$(document).ready(function () {
			EE.cp.roleToggleDisable();
		});');

		return $sections;
	}

	private function remove($role_ids)
	{
		if ( ! ee('Permission')->can('delete_roles'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($role_ids))
		{
			$role_ids = array($role_ids);
		}

		$roles = ee('Model')->get('Role', $role_ids)->all();

		$role_names = $roles->pluck('name');

		$roles->delete();
		ee('CP/Alert')->makeInline('roles')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('roles_removed_desc'))
			->addToBody($role_names)
			->defer();

		foreach ($role_names as $role_name)
		{
			ee()->logger->log_action(sprintf(lang('removed_role'), '<b>' . $role_name . '</b>'));
		}
	}
}

// EOF
