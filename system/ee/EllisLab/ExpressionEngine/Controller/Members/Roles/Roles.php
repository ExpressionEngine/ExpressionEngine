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
		$search = $filter_values['filter_by_keyword'];

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
			ee('CP/URL')->make('members/roles')->compile() => lang('role_manager')
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
			'sections' => $this->form($role),
				? ee('CP/URL')->make('members/roles/create/'.$group_id)
				: ee('CP/URL')->make('members/roles/create'),
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

		ee()->javascript->set_global([
			'publish.foreignChars' => ee()->config->loadFile('foreign_chars')
		]);

		ee()->javascript->output('
			$("input[name=name]").bind("keyup keydown", function() {
				$(this).ee_url_title("input[name=role_name]", true);
			});
		');

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
			ee('CP/URL')->make('roles')->compile() => lang('role_manager'),
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
			'sections' => $this->form($role),
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
		$role->role_list_items = ($role->role_list_items) ?: '';
		$role->role_order = ($role->role_order) ?: 0;
		$role->site_id = (int) $role->site_id ?: 0;

		$role->set($_POST);

		if ($role->role_pre_populate)
		{
			list($channel_id, $role_id) = explode('_', $_POST['role_pre_populate_id']);

			$role->role_pre_channel_id = $channel_id;
			$role->role_pre_role_id = $role_id;
		}

		return $role;
	}

	private function form(Role $role = NULL)
	{
		if ( ! $role)
		{
			$role = ee('Model')->make('Role');
		}

		$roletype_choices = $role->getCompatibleFieldtypes();

		$roletypes = ee('Model')->get('Fieldtype')
			->roles('name')
			->filter('name', 'IN', array_keys($roletype_choices))
			->order('name')
			->all();

		$role->role_type = ($role->role_type) ?: 'text';

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
