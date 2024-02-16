<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Roles;

use ExpressionEngine\Controller\Members\Roles\AbstractRoles as AbstractRolesController;
use ExpressionEngine\Model\Role\Role;
use ExpressionEngine\Service\Member\Member;

/**
 * Members\Roles\Roles Controller
 */
class Roles extends AbstractRolesController
{
    public function index()
    {
        $group_id = ee('Request')->get('group_id');

        if ($group_id) {
            $base_url = ee('CP/URL')->make('members/roles', ['group_id' => $group_id]);
        } else {
            $base_url = ee('CP/URL')->make('members/roles');
        }

        if (ee('Request')->post('bulk_action') == 'remove') {
            $this->remove(ee('Request')->post('selection'));
            ee()->functions->redirect($base_url);
        }

        $this->generateSidebar($group_id);

        $vars['create_url'] = $group_id
            ? ee('CP/URL')->make('members/roles/create/' . $group_id)
            : ee('CP/URL')->make('members/roles/create');
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

        $filters->add($group_filter)
            ->add('Keyword');

        $filter_values = $filters->values();
        $search = isset($filter_values['filter_by_keyword']) ? $filter_values['filter_by_keyword'] : null;

        $total_roles = 0;

        $group = $group_id && $group_id != 'all'
            ? ee('Model')->get('RoleGroup', $group_id)->first()
            : null;

        // Are we showing a specific group? If so, we need to apply filtering differently
        // because we are acting on a collection instead of a query builder
        if ($group) {
            $roles = $group->Roles->sortBy('name')->asArray();

            if ($search) {
                $roles = array_filter($roles, function ($role) use ($search) {
                    return strpos(
                        strtolower($role->name),
                        strtolower($search)
                    ) !== false;
                });
            }

            $total_roles = count($roles);
        } else {
            $roles = ee('Model')->get('Role');

            if ($search) {
                $roles->search(['name'], $search);
            }

            $total_roles = $roles->count();
        }

        $filters->add('Perpage', $total_roles, 'all_roles', true);

        $filter_values = $filters->values();
        $vars['base_url']->addQueryStringVariables($filter_values);
        $per_page = $filter_values['perpage'];

        if ($group) {
            $roles = array_slice($roles, (($page - 1) * $per_page), $per_page);
        } else {
            $roles = $roles->limit($per_page)
                ->offset(($page - 1) * $per_page)
                ->order('name')
                ->all();
        }

        // Only show filters if there is data to filter or we are currently filtered
        if ($group_id or ! empty($roles)) {
            $vars['filters'] = $filters->render(ee('CP/URL')->make('members/roles'));
        }

        $cpRoles = ee('Permission')->rolesThatHave('can_access_cp');
        foreach ($roles as $role) {
            $edit_url = (ee('Permission')->hasAny('can_edit_roles')) ? ee('CP/URL')->make('members/roles/edit/' . $role->getId()) : '';
            $labelVars = [
                'label' => $role->name,
                'class' => str_replace(' ', '_', strtolower($role->name)),
                'styles' => [
                    'background-color' => 'var(--ee-bg-blank)',
                    'border-color' => '#' . $role->highlight,
                    'color' => '#' . $role->highlight,
                ]
            ];
            $label = ee('View')->make('_shared/status-tag')->render($labelVars);
            $badges = '<i class="fal fa-lock' . ($role->is_locked ? '' : '-open') . '" title=""></i> ' . lang(($role->is_locked ? '' : 'un') . 'locked');
            if (in_array($role->role_id, $cpRoles)) {
                $badges .= ' / <i class="fal fa-shield" style="color: var(--ee-security-caution)" title=""></i> ' . lang('cp_access');
            }
            $data[] = [
                'id' => $role->getId(),
                'label' => $label,
                'extra' => $badges,
                'href' => $edit_url,
                'selected' => ($role_id && $role->getId() == $role_id),
                'toolbar_items' => [
                    'members' => [
                        'href' => ee('CP/URL', 'members', ['role_filter' => $role->getId()]),
                        'title' => lang('members'),
                        'content' => '<i class="fal fa-users"></i> ' . $role->total_members . ' ' . lang('members')
                    ]
                ],
                'selection' => ee('Permission')->can('delete_roles') ? [
                    'name' => 'selection[]',
                    'value' => $role->getId(),
                    'data' => [
                        'confirm' => lang('role') . ': <b>' . ee('Format')->make('Text', $role->name)->convertToEntities() . '</b>'
                    ],
                    'disabled' => in_array($role->getId(), [])//$this->getRestrictedRoles())
                ] : null
            ];
        }

        if (ee('Permission')->can('delete_roles')) {
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
            ? $group->name . '&mdash;' . lang('roles')
            : lang('all_roles');
        $vars['roles'] = $data;
        $vars['disable_action'] = (bool) $group_id;
        if ($group_id) {
            $vars['no_results'] = ['text' => sprintf(lang('no_found'), lang('roles')) . ' &mdash; ' . $group->name];
        } else {
            $vars['no_results'] = ['text' => sprintf(lang('no_found'), lang('roles')), 'href' => $vars['create_url']];
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('roles')
        );

        if (bool_config_item('ignore_member_stats')) {
            ee()->lang->load('members');
            ee('CP/Alert')->makeInline('roles-count-warn')
                ->asWarning()
                ->addToBody(lang('roles_counter_warning'))
                ->cannotClose()
                ->now();
        }

        ee()->cp->render('members/roles/index', $vars);
    }

    public function create($group_id = null)
    {
        if (! ee('Permission')->can('create_roles')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (ee('Request')->post('group_id')) {
            $group_id = ee('Request')->post('group_id');
        }

        $this->generateSidebar($group_id);

        $errors = null;
        $role = ee('Model')->make('Role');
        $role->AssignedStatuses = ee('Model')->get('Status')->all();

        if ($group_id) {
            $role->RoleGroups = ee('Model')->get('RoleGroup', $group_id)->all();
        }

        if (! empty($_POST)) {
            $role = $this->setWithPost($role);
            $result = $role->validate();

            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $role->save();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('create_role_success'))
                    ->addToBody(sprintf(lang('create_role_success_desc'), $role->name))
                    ->defer();

                if (AJAX_REQUEST) {
                    return ['saveId' => $role->getId()];
                }

                if (ee('Request')->post('submit') == 'save_and_new') {
                    $return = (empty($group_id)) ? '' : '/' . $group_id;
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/create' . $return));
                } elseif (ee('Request')->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles'));
                } else {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/edit/' . $role->getId()));
                }
            } else {
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
            'ajax_validate' => true,
            'base_url' => $group_id
                ? ee('CP/URL')->make('members/roles/create/' . $group_id)
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
                'role_id' => null
            ),
        );

        if (AJAX_REQUEST) {
            unset($vars['buttons'][2]);
        }

        ee()->javascript->output('
            $("input[name=name]").bind("keyup keydown", function() {
                $(this).ee_url_title("input[name=short_name]");
            });
        ');

        ee()->view->cp_page_title = lang('create_new_role');

        ee()->view->extra_alerts = array('search-reindex'); // for Save & New

        if (AJAX_REQUEST) {
            return ee()->cp->render('_shared/form', $vars);
        }

        ee()->cp->add_js_script('plugin', 'ee_url_title');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/roles')->compile() => lang('roles'),
            '' => lang('create_new_role')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function edit($id)
    {
        if (! ee('Permission')->can('edit_roles')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $role = ee('Model')->get('Role', $id)
            ->first();

        if (! $role) {
            show_404();
        }

        $role_groups = $role->RoleGroups;
        $active_groups = $role_groups->pluck('group_id');

        if (defined('CLONING_MODE') && CLONING_MODE === true) {
            $role->setId(null);
            while (true !== $role->validateUnique('short_name', $_POST['short_name'])) {
                $_POST['short_name'] = 'copy_' . $_POST['short_name'];
            }
            while (true !== $role->validateUnique('name', $_POST['name'])) {
                $_POST['name'] = lang('copy_of') . ' ' . $_POST['name'];
            }
            return $this->create(!empty($active_groups) ? $active_groups[0] : null);
        }

        $this->generateSidebar($active_groups);

        $errors = null;

        if (! empty($_POST)) {
            $role = $this->setWithPost($role);
            $role->total_members = null; //force recalculation
            $result = $role->validate();

            if ($response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $role->save();

                if (ee('Request')->post('update_formatting') == 'y') {
                    ee()->db->where('role_ft_' . $role->role_id . ' IS NOT NULL', null, false);
                    ee()->db->update(
                        $role->getDataStorageTable(),
                        array('role_ft_' . $role->role_id => $role->role_fmt)
                    );
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('edit_role_success'))
                    ->addToBody(sprintf(lang('edit_role_success_desc'), $role->name))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/create'));
                } elseif (ee('Request')->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles'));
                } else {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/edit/' . $role->getId()));
                }
            } else {
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
            'ajax_validate' => true,
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

        if ($role->getId() != 1) {
            $vars['buttons'][] = [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_as_new_entry',
                'text' => sprintf(lang('clone_to_new'), lang('role')),
                'working' => 'btn_saving'
            ];
        }

        ee()->view->cp_page_title = lang('edit_role');
        ee()->view->extra_alerts = array('search-reindex');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/roles')->compile() => lang('roles'),
            '' => lang('edit_role')
        );

        ee()->cp->render('settings/form', $vars);
    }

    private function setWithPost(Role $role)
    {
        $site_id = ee()->config->item('site_id');

        $role_groups = !empty(ee('Request')->post('role_groups')) ? ee('Request')->post('role_groups') : array();

        // can't use ee('Request') because $_POST could have been modified for cloning
        $role->name = ee('Security/XSS')->clean($_POST['name']);
        $role->short_name = ee('Security/XSS')->clean($_POST['short_name']);
        $role->description = ee('Security/XSS')->clean(ee('Request')->post('description'));
        $role->highlight = ee('Security/XSS')->clean(ee('Request')->post('highlight'));

        // Settings
        $settings = ee('Model')->make('RoleSetting')->getValues();
        unset($settings['id'], $settings['role_id'], $settings['site_id']);

        foreach (array_keys($settings) as $key) {
            if (ee('Request')->post($key) !== null) {
                $settings[$key] = ee('Request')->post($key);
            } else {
                unset($settings[$key]);
            }
        }

        if ($role->isNew()) {
            // Apply these to all sites
            $sites = ee('Model')->get('Site')->all();
            foreach ($sites as $site) {
                $role_settings = ee('Model')->make('RoleSetting', ['site_id' => $site->getId()]);
                $role_settings->set($settings);
                $role->RoleSettings->getAssociation()->add($role_settings);
            }
        } else {
            $role_settings = $role->RoleSettings->indexBy('site_id');
            $role_settings = $role_settings[$site_id];

            $role_settings->set($settings);
        }

        //We don't allow much editing for SuperAdmin role, so just enforce it's locked and return here
        if ($role->getId() == 1) {
            $role->is_locked = 'y';

            return $role;
        }
        $role->is_locked = ee('Request')->post('is_locked');
        $role->RoleGroups = ee('Model')->get('RoleGroup', $role_groups)->all();
        $role->AssignedModules = ee('Model')->get('Module', ee('Request')->post('addons_access'))->all();

        $uploadDestinationIds = !empty(ee('Request')->post('upload_destination_access')) ? ee('Request')->post('upload_destination_access') : array();
        $assignedUploadDestinations = $role->AssignedUploadDestinations->getDictionary('id', 'site_id');
        if (!empty($assignedUploadDestinations)) {
            foreach ($assignedUploadDestinations as $dest_id => $dest_site_id) {
                if ($dest_site_id != 0 && $dest_site_id != $site_id) {
                    $uploadDestinationIds[] = $dest_id;
                }
            }
        }
        $role->AssignedUploadDestinations = ee('Model')->get('UploadDestination', $uploadDestinationIds)->all();

        $allowed_perms = [];

        // channel_access
        $channel_ids = [];
        if (!empty(ee('Request')->post('channel_access'))) {
            foreach (ee('Request')->post('channel_access') as $value) {
                if (strpos($value, 'channel_id_') !== 0) {
                    $allowed_perms[] = $value;
                    $value_exploded = explode('channel_id_', $value);
                    $channel_ids[] = end($value_exploded);
                }
            }
        }

        $assignedChannels = $role->AssignedChannels->getDictionary('channel_id', 'site_id');
        if (!empty($assignedChannels)) {
            foreach ($assignedChannels as $channel_id => $channel_site_id) {
                if ($channel_site_id != $site_id) {
                    $channel_ids[] = $channel_id;
                }
            }
        }

        if (! empty($channel_ids)) {
            $role->AssignedChannels = ee('Model')->get('Channel', $channel_ids)->all();
        }

        // template_group_access
        $template_group_ids = [];
        if (!empty(ee('Request')->post('template_group_access'))) {
            foreach (ee('Request')->post('template_group_access') as $value) {
                if (strpos($value, 'template_group_') === 0) {
                    $template_group_ids[] = str_replace('template_group_', '', $value);
                } else {
                    $allowed_perms[] = $value;
                }
            }
        }

        if (! empty($template_group_ids)) {
            $role->AssignedTemplateGroups = ee('Model')->get('TemplateGroup', $template_group_ids)->all();
        }

        // template_access
        $template_ids = [];
        // ensure templates from other sites are always in
        if (!$role->isNew() && bool_config_item('multiple_sites_enabled')) {
            $template_ids = $role->AssignedTemplates->filter('site_id', '!=', ee()->config->item('site_id'))->pluck('template_id');
        }
        // add posted template IDs
        if (!empty(ee('Request')->post('assigned_templates'))) {
            $posted_assigned_templates = ee('Request')->post('assigned_templates');
            if (!is_array($posted_assigned_templates) && strpos($posted_assigned_templates, '[') === 0) {
                $posted_assigned_templates = json_decode($posted_assigned_templates);
            }
            foreach ($posted_assigned_templates as $value) {
                if (is_numeric($value)) {
                    $template_ids[] = $value;
                }
            }
        }
        if (!empty($template_ids)) {
            $role->AssignedTemplates = ee('Model')->get('Template', $template_ids)->all();
        }

        // Permissions
        $permissions = $this->getPermissions();

        foreach (array_keys($permissions['choices']) as $key) {
            $perms = ee('Request')->post($key);
            if (! empty($perms) and ! empty($perms[0])) {
                $allowed_perms = array_merge($allowed_perms, $perms);
            }
        }

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'can_') === 0 && $value == 'y') {
                $allowed_perms[] = $key;
            }
        }

        if ($role->isNew()) {
            // Apply these to all sites
            $sites = ee('Model')->get('Site')->all();
            foreach ($sites as $site) {
                foreach ($allowed_perms as $perm) {
                    if (!empty($perm)) {
                        $p = ee('Model')->make('Permission', [
                            'site_id' => $site->getId(),
                            'permission' => $perm
                        ]);
                        $role->Permissions->getAssociation()->add($p);
                    }
                }
            }
        } else {
            $existingRolePermissions = ee('Model')->get('Permission')
                ->filter('permission', 'IN', $this->getPermissionKeys())
                ->filter('site_id', $site_id)
                ->filter('role_id', $role->getId())
                ->all();
            $existingRolePermissionNames = $existingRolePermissions->pluck('permission');
            // remove the permissions that are no longer allowed
            foreach ($existingRolePermissions as $permission) {
                $permIndex = array_search($permission->permission, $allowed_perms);
                if ($permIndex === false || empty($allowed_perms[$permIndex])) {
                    $role->Permissions->getAssociation()->remove($permission);
                }
            }
            // Add back in all the allowances
            foreach ($allowed_perms as $perm) {
                if (!empty($perm) && !in_array($perm, $existingRolePermissionNames)) {
                    $p = ee('Model')->make('Permission', [
                        'site_id' => $site_id,
                        'permission' => $perm
                    ]);
                    $role->Permissions->getAssociation()->add($p);
                }
            }
        }

        return $role;
    }

    private function getTabs(Role $role, $errors)
    {
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/form_group',
                'library/simplecolor',
                'components/colorpicker'
            ),
        ));

        $tabs = [
            'role' => $this->renderRoleTab($role, $errors)
        ];

        if ($role->getId() != Member::SUPERADMIN) {
            $tabs['site_access'] = $this->renderSiteAccessTab($role, $errors);
            if ($role->getId() != Member::GUESTS) {
                $tabs['cp_access'] = $this->renderCPAccessTab($role, $errors);
            }
            $tabs['template_access'] = $this->renderTemplateAccessTab($role, $errors);
        }

        return $tabs;
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
                        'required' => true,
                        'value' => $role->name
                    ]
                ]
            ],
            [
                'title' => 'short_name',
                'desc' => 'alphadash_desc',
                'fields' => [
                    'short_name' => [
                        'type' => 'text',
                        'required' => true,
                        'value' => $role->short_name
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
                'title' => 'role_highlight_color',
                'desc' => 'role_highlight_color_desc',
                'fields' => array(
                    'highlight' => array(
                        'type' => 'text',
                        'attrs' => 'class="color-picker"',
                        'value' => $role->highlight ?: 'FA5252'
                    )
                )
            ]
        ];

        if (!in_array($role->getId(), [Member::BANNED, Member::GUESTS, Member::PENDING])) {
            ee()->lang->load('pro');
            $section = array_merge($section, [
                [
                    'title' => 'require_mfa',
                    'desc' => 'require_mfa_desc',
                    'caution' => true,
                    'fields' => [
                        'require_mfa' => [
                            'type' => 'yes_no',
                            'value' => $role->isNew() ? 'n' : $role->RoleSettings->filter('site_id', ee()->config->item('site_id'))->first()->require_mfa,
                        ]
                    ]
                ],
            ]);
        }

        if (!in_array($role->getId(), [Member::SUPERADMIN, Member::BANNED, Member::GUESTS, Member::PENDING])) {
            if ($role->getId() != Member::SUPERADMIN) {
                $section = array_merge($section, [
                    [
                        'title' => 'security_lock',
                        'desc' => 'lock_description',
                        'fields' => [
                            'is_locked' => [
                                'type' => 'yes_no',
                                'value' => $role->is_locked,
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
                                'value' => $role->RoleGroups->pluck('group_id'),
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('role_groups')),
                                    'link_text' => lang('add_new'),
                                    'link_href' => ee('CP/URL')->make('members/roles/groups/create')->compile()
                                ]
                            ]
                        ]
                    ]
                ]);
            }
        }

        if ($role->getId() == Member::SUPERADMIN) {
            // superadmins don't have other tabs, so including checkboxes here
            $section = array_merge($section, [
                [
                    'title' => 'include_members_in',
                    'desc' => 'include_members_in_desc',
                    'fields' => [
                        'include_in_authorlist' => [
                            'type' => 'checkbox',
                            'choices' => ['y' => lang('include_in_authorlist')],
                            'scalar' => true,
                            'value' => $settings->include_in_authorlist
                        ],
                        'include_in_memberlist' => [
                            'type' => 'checkbox',
                            'margin_top' => false,
                            'scalar' => true,
                            'choices' => ['y' => lang('include_in_memberlist')],
                            'value' => $settings->include_in_memberlist
                        ]
                    ]
                ]
            ]);

            $section = array_merge($section, [
                [
                    'title' => 'show_field_names',
                    'desc' => 'show_field_names_desc',
                    'fields' => [
                        'show_field_names' => [
                            'type' => 'yes_no',
                            'value' => $role->RoleSettings->filter('site_id', ee()->config->item('site_id'))->first()->show_field_names,
                        ]
                    ]
                ],
            ]);
        }

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    private function renderSiteAccessTab(Role $role, $errors)
    {
        $settings = $role->RoleSettings->indexBy('site_id');
        $site_id = ee()->config->item('site_id');

        $settings = (isset($settings[$site_id])) ? $settings[$site_id] : ee('Model')->make('RoleSetting', ['site_id' => $site_id]);

        $permissions = $this->getPermissions($role);

        $sections = [
            [
                [
                    'title' => 'site_access',
                    'desc' => 'site_access_desc',
                    'fields' => [
                        'website_access' => [
                            'type' => 'checkbox',
                            'choices' => $permissions['choices']['website_access'],
                            'value' => $permissions['values']['website_access'],
                            'encode' => false
                        ]
                    ]
                ],
                [
                    'title' => 'can_view_profiles',
                    'desc' => 'can_view_profiles_desc',
                    'fields' => [
                        'can_view_profiles' => $permissions['fields']['can_view_profiles']
                    ]
                ],
            ]
        ];
        if ($role->getId() != Member::GUESTS) {
            $sections[0] = array_merge($sections[0], [
                [
                    'title' => 'can_delete_self',
                    'desc' => 'can_delete_self_desc',
                    'fields' => [
                        'can_delete_self' => $permissions['fields']['can_delete_self']
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
            ]);
        }
        $sections[0][] = [
            'title' => 'include_members_in',
            'desc' => 'include_members_in_desc',
            'fields' => [
                'include_in_authorlist' => [
                    'type' => 'checkbox',
                    'choices' => ['y' => lang('include_in_authorlist')],
                    'scalar' => true,
                    'value' => $settings->include_in_authorlist
                ],
                'include_in_memberlist' => [
                    'type' => 'checkbox',
                    'margin_top' => false,
                    'scalar' => true,
                    'choices' => ['y' => lang('include_in_memberlist')],
                    'value' => $settings->include_in_memberlist
                ]
            ]
        ];
        $sections['commenting'] = [
            [
                'title' => 'can_post_comments',
                'desc' => 'can_post_comments_desc',
                'fields' => [
                    'can_post_comments' => $permissions['fields']['can_post_comments']
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
        ];
        if ($role->getId() != Member::GUESTS) {
            $sections['commenting'][] = [
                'title' => 'comment_actions',
                'desc' => 'comment_actions_desc',
                'caution' => true,
                'fields' => [
                    'comment_actions' => [
                        'type' => 'checkbox',
                        'choices' => $permissions['choices']['comment_actions'],
                        'value' => $permissions['values']['comment_actions'],
                    ]
                ]
            ];
        }
        $sections['search'] = [
            [
                'title' => 'can_search',
                'desc' => 'can_search_desc',
                'fields' => [
                    'can_search' => $permissions['fields']['can_search']
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
        ];
        if ($role->getId() != Member::GUESTS) {
            $sections['personal_messaging'] = [
                [
                    'title' => 'can_send_private_messages',
                    'desc' => 'can_send_private_messages_desc',
                    'fields' => [
                        'can_send_private_messages' => $permissions['fields']['can_send_private_messages']
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
                        'can_attach_in_private_messages' => $permissions['fields']['can_attach_in_private_messages']
                    ]
                ],
                [
                    'title' => 'can_send_bulletins',
                    'desc' => 'can_send_bulletins_desc',
                    'group' => 'can_access_pms',
                    'fields' => [
                        'can_send_bulletins' => $permissions['fields']['can_send_bulletins']
                    ]
                ],
            ];
        }

        $html = '';

        foreach ($sections as $name => $settings) {
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
            'overview' => lang('cp_overview') . ' &mdash; <i>' . lang('default') . '</i>',
            'entries_edit' => lang('edit_listing')
        );

        $allowed_channels = ee('Model')->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all()
            ->getDictionary('channel_id', 'channel_title');

        $channel_access = $this->getChannelAccess($allowed_channels, $role);
        $template_group_access = $this->getTemplateGroupAccess($role);

        if (count($allowed_channels)) {
            $default_homepage_choices['publish_form'] = lang('publish_form') . ' &mdash; ' .
                form_dropdown('cp_homepage_channel', $allowed_channels, $settings->cp_homepage_channel);
        }

        $default_homepage_choices['custom'] = lang('custom_uri');
        $default_homepage_value = [];

        $addons = ee('Model')->get('Module')
            ->fields('module_id', 'module_name')
            ->all()
            ->filter(function ($addon) {
                $provision = ee('Addon')->get(strtolower($addon->module_name));

                if (! $provision) {
                    return false;
                }

                if ($provision->get('built_in')) {
                    return false;
                }

                $addon->module_name = $provision->getName();

                return true;
            })
            ->getDictionary('module_id', 'module_name');

        $allowed_upload_destinations = ee('Model')->get('UploadDestination')
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->filter('module_id', 0)
            ->all()
            ->getDictionary('id', 'name');

        $permissions = $this->getPermissions($role);

        $sections = [
            [
                [
                    'title' => 'can_access_cp',
                    'desc' => 'can_access_cp_desc',
                    'caution' => true,
                    'fields' => [
                        'can_access_cp' => $permissions['fields']['can_access_cp']
                    ]
                ],
                [
                    'title' => 'can_access_dock',
                    'desc' => 'can_access_dock_desc',
                    'group' => 'can_access_cp',
                    'caution' => true,
                    'fields' => [
                        'can_access_dock' => $permissions['fields']['can_access_dock']
                    ]
                ],
            ]
        ];

        $sections = array_merge($sections, [
            [
                [
                    'title' => 'default_cp_homepage',
                    'desc' => 'default_cp_homepage_desc',
                    'group' => 'can_access_cp',
                    'fields' => [
                        'cp_homepage' => [
                            'type' => 'radio',
                            'choices' => $default_homepage_choices,
                            'value' => $settings->cp_homepage ?: $default_homepage_value,
                            'encode' => false
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
                            'choices' => $permissions['choices']['footer_helper_links'],
                            'value' => $permissions['values']['footer_helper_links'],
                        ]
                    ]
                ],
                [
                    'title' => 'homepage_news',
                    'desc' => 'homepage_news_desc',
                    'group' => 'can_access_cp',
                    'fields' => [
                        'can_view_homepage_news' => $permissions['fields']['can_view_homepage_news']
                    ]
                ],
            ],
            'channels' => [
                'group' => 'can_access_cp',
                'settings' => [
                    [
                        'title' => 'can_admin_channels',
                        'desc' => 'can_admin_channels_desc',
                        'caution' => true,
                        'fields' => [
                            'can_admin_channels' => $permissions['fields']['can_admin_channels']
                        ]
                    ],
                    [
                        'title' => 'channels',
                        'desc' => 'allowed_actions_desc',
                        'group' => 'can_admin_channels',
                        'fields' => [
                            'channel_permissions' => [
                                'type' => 'checkbox',
                                'choices' => $permissions['choices']['channel_permissions'],
                                'value' => $permissions['values']['channel_permissions'],
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
                                'choices' => $permissions['choices']['channel_field_permissions'],
                                'value' => $permissions['values']['channel_field_permissions'],
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
                                'choices' => $permissions['choices']['channel_category_permissions'],
                                'value' => $permissions['values']['channel_category_permissions'],
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
                                'choices' => $permissions['choices']['channel_status_permissions'],
                                'value' => $permissions['values']['channel_status_permissions'],
                            ]
                        ]
                    ],
                ]
            ],
            'channel_entries_management' => [
                [
                    'title' => 'channel_access',
                    'desc' => 'channel_access_desc',
                    'caution' => true,
                    'fields' => [
                        'channel_access' => [
                            'type' => 'checkbox',
                            'nested' => true,
                            'auto_select_parents' => true,
                            'choices' => $channel_access['choices'],
                            'value' => $channel_access['values'],
                        ]
                    ]
                ],
                [
                    'title' => 'show_field_names',
                    'desc' => 'show_field_names_desc',
                    'fields' => [
                        'show_field_names' => [
                            'type' => 'yes_no',
                            'value' => $role->isNew() ? 'n' : $role->RoleSettings->filter('site_id', ee()->config->item('site_id'))->first()->show_field_names,
                        ]
                    ]
                ],
            ],
            'file_manager' => [
                'group' => 'can_access_cp',
                'settings' => [
                    [
                        'title' => 'can_access_file_manager',
                        'desc' => 'file_manager_desc',
                        'fields' => [
                            'can_access_files' => $permissions['fields']['can_access_files']
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
                        'title' => 'upload_destination_access',
                        'desc' => 'upload_destination_access_desc',
                        'caution' => true,
                        'group' => 'can_access_files',
                        'fields' => [
                            'upload_destination_access' => [
                                'type' => 'checkbox',
                                'choices' => $allowed_upload_destinations,
                                'value' => $role->AssignedUploadDestinations->pluck('id')
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
                            'can_access_members' => $permissions['fields']['can_access_members']
                        ]
                    ],
                    [
                        'title' => 'can_admin_roles',
                        'desc' => 'can_admin_roles_desc',
                        'caution' => true,
                        'group' => 'can_access_members',
                        'fields' => [
                            'can_admin_roles' => $permissions['fields']['can_admin_roles']
                        ]
                    ],
                    [
                        'title' => 'roles',
                        'desc' => 'allowed_actions_desc',
                        'group' => 'can_access_members',
                        'caution' => true,
                        'fields' => [
                            'role_actions' => [
                                'type' => 'checkbox',
                                'choices' => $permissions['choices']['role_actions'],
                                'value' => $permissions['values']['role_actions'],
                            ]
                        ]
                    ],
                    [
                        'title' => lang('members'),
                        'desc' => 'allowed_actions_desc',
                        'group' => 'can_access_members',
                        'caution' => true,
                        'fields' => [
                            'member_actions' => [
                                'type' => 'checkbox',
                                'choices' => $permissions['choices']['member_actions'],
                                'value' => $permissions['values']['member_actions'],
                                'encode' => false
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
                            'can_access_design' => $permissions['fields']['can_access_design']
                        ]
                    ],
                    [
                        'title' => 'can_admin_design',
                        'desc' => 'can_admin_design_desc',
                        'group' => 'can_access_design',
                        'caution' => true,
                        'fields' => [
                            'can_admin_design' => $permissions['fields']['can_admin_design']
                        ]
                    ],
                    [
                        'title' => 'template_groups',
                        'desc' => 'allowed_actions_desc',
                        'group' => 'can_access_design',
                        'caution' => true,
                        'fields' => [
                            'template_group_permissions' => [
                                'type' => 'checkbox',
                                'choices' => $permissions['choices']['template_group_permissions'],
                                'value' => $permissions['values']['template_group_permissions']
                            ]
                        ]
                    ],
                    [
                        'title' => 'template_partials',
                        'desc' => 'allowed_actions_desc',
                        'group' => 'can_access_design',
                        'caution' => true,
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
                        'caution' => true,
                        'fields' => [
                            'template_variables' => [
                                'type' => 'checkbox',
                                'choices' => $permissions['choices']['template_variables'],
                                'value' => $permissions['values']['template_variables']
                            ]
                        ]
                    ],
                    [
                        'title' => 'template_group_access',
                        'desc' => 'template_group_access_desc',
                        'caution' => true,
                        'fields' => [
                            'template_group_access' => [
                                'type' => 'checkbox',
                                'nested' => true,
                                'auto_select_parents' => true,
                                'choices' => $template_group_access['choices'],
                                'value' => $template_group_access['values'],
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
                            'can_access_addons' => $permissions['fields']['can_access_addons']
                        ]
                    ],
                    [
                        'title' => 'can_admin_addons',
                        'desc' => 'can_admin_addons_desc',
                        'group' => 'can_access_addons',
                        'caution' => true,
                        'fields' => [
                            'can_admin_addons' => $permissions['fields']['can_admin_addons']
                        ]
                    ],
                    [
                        'title' => 'addons_access',
                        'desc' => 'addons_access_desc',
                        'group' => 'can_access_addons',
                        'caution' => true,
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
                ]
            ],
            'tools_utilities' => [
                'group' => 'can_access_cp',
                'settings' => [
                    [
                        'title' => 'access_utilities',
                        'desc' => 'access_utilities_desc',
                        'fields' => [
                            'can_access_utilities' => $permissions['fields']['can_access_utilities']
                        ]
                    ],
                    [
                        'title' => 'utilities_section',
                        'desc' => 'utilities_section_desc',
                        'group' => 'can_access_utilities',
                        'caution' => true,
                        'fields' => [
                            'access_tools' => [
                                'type' => 'checkbox',
                                'nested' => true,
                                'auto_select_parents' => true,
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
                            'can_access_logs' => $permissions['fields']['can_access_logs']
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
                        'caution' => true,
                        'fields' => [
                            'can_access_sys_prefs' => $permissions['fields']['can_access_sys_prefs']
                        ]
                    ],
                    [
                        'title' => 'can_access_security_settings',
                        'desc' => 'can_access_security_settings_desc',
                        'group' => 'can_access_sys_prefs',
                        'caution' => true,
                        'fields' => [
                            'can_access_security_settings' => $permissions['fields']['can_access_security_settings']
                        ]
                    ],
                    [
                        'title' => 'can_manage_consents',
                        'desc' => 'can_manage_consents_desc',
                        'group' => 'can_access_sys_prefs',
                        'caution' => true,
                        'fields' => [
                            'can_manage_consents' => $permissions['fields']['can_manage_consents']
                        ]
                    ]
                ]
            ]
        ]);

        $html = '';

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    private function renderTemplateAccessTab(Role $role, $errors)
    {
        $template_groups = ee('Model')->get('TemplateGroup')
            ->fields('group_id', 'group_name')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name')
            ->all()
            ->getDictionary('group_id', 'group_name');

        $template_access = [
            'choices' => [],
            'values' => []
        ];
        foreach ($template_groups as $id => $name) {
            $templates = ee('Model')->get('Template')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('group_id', $id)
                ->all();
            $children = [];
            foreach ($templates as $template) {
                $template_name = $template->template_name;
                if ($template->enable_http_auth == 'y') {
                    $template_name = '<i class="fal fa-key fa-sm icon-left" title="' . lang('http_auth_protected') . '"></i>' . $template_name;
                }
                $children[$template->getId()] = $template_name;
            }
            $template_access['choices']['template_group_' . $id] = [
                'label' => $name,
                'children' => $children
            ];
        }

        if ($role && !empty($role->getId())) {
            $assigned_template_groups = $role->AssignedTemplateGroups;
        } else {
            $assigned_template_groups = ee('Model')->get('TemplateGroup')
                ->filter('site_id', ee()->config->item('site_id'))
                ->all();
        }
        foreach ($assigned_template_groups as $template_group) {
            $template_access['values'][] = 'template_group_' . $template_group->getId();
        }

        if ($role && !empty($role->getId())) {
            $assigned_templates = $role->AssignedTemplates;
        } else {
            $assigned_templates = ee('Model')->get('Template')
                ->filter('site_id', ee()->config->item('site_id'))
                ->all();
        }
        foreach ($assigned_templates as $template) {
            $template_access['values'][] = $template->getId();
        }

        $section = [
            [
                'title' => 'assigned_templates',
                'desc' => 'assigned_templates_desc',
                'fields' => [
                    'assigned_templates' => [
                        'type' => 'checkbox',
                        'nested' => true,
                        'auto_select_parents' => true,
                        'jsonify' => true,
                        'choices' => $template_access['choices'],
                        'value' => $template_access['values'],
                    ]
                ]
            ]
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    private function getChannelAccess($channels, Role $role = null)
    {
        $channel_access = [
            'choices' => [],
            'values' => []
        ];
        foreach ($channels as $id => $title) {
            $channel_access['choices']['channel_id_' . $id] = [
                'label' => $title,
                'children' => [
                    'can_create_entries_channel_id_' . $id => lang('can_create_entries'),
                    'can_edit_self_entries_channel_id_' . $id => lang('can_edit_self_entries'),
                    'can_delete_self_entries_channel_id_' . $id => lang('can_delete_self_entries'),
                    'can_edit_other_entries_channel_id_' . $id => lang('can_edit_other_entries'),
                    'can_delete_all_entries_channel_id_' . $id => lang('can_delete_all_entries'),
                    'can_assign_post_authors_channel_id_' . $id => lang('can_assign_post_authors')
                ]
            ];
        }

        if ($role) {
            foreach ($role->AssignedChannels as $channel) {
                if ($channel->site_id == ee()->config->item('site_id')) {
                    $channel_access['values'][] = 'channel_id_' . $channel->getId();
                }
            }

            foreach ($channel_access['choices'] as $group => $choices) {
                $channel_access['values'] = array_merge($channel_access['values'], $this->getPermissionValues($role, $choices['children']));
            }
        }

        return $channel_access;
    }

    private function getTemplateGroupAccess(Role $role = null)
    {
        $template_groups = ee('Model')->get('TemplateGroup')
            ->fields('group_id', 'group_name')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name')
            ->all()
            ->getDictionary('group_id', 'group_name');

        $template_group_access = [
            'choices' => [],
            'values' => []
        ];
        foreach ($template_groups as $id => $name) {
            $template_group_access['choices']['template_group_' . $id] = [
                'label' => $name,
                'children' => [
                    'can_create_templates_template_group_id_' . $id => lang('can_create_templates'),
                    'can_edit_templates_template_group_id_' . $id => lang('can_edit_templates'),
                    'can_delete_templates_template_group_id_' . $id => lang('can_delete_templates'),
                    'can_manage_settings_template_group_id_' . $id => lang('can_manage_settings'),
                ]
            ];
        }

        if ($role) {
            foreach ($role->AssignedTemplateGroups as $template_group) {
                $template_group_access['values'][] = 'template_group_' . $template_group->getId();
            }

            foreach ($template_group_access['choices'] as $group => $choices) {
                $template_group_access['values'] = array_merge($template_group_access['values'], $this->getPermissionValues($role, $choices['children']));
            }
        }

        return $template_group_access;
    }

    private function getPermissions(Role $role = null)
    {
        $permissions = [
            'fields' => [
                'can_admin_channels' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_admin_channels'
                    ]
                ],
                'can_access_files' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_files'
                    ]
                ],
                'can_access_members' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_members'
                    ]
                ],
                'can_admin_roles' => [
                    'type' => 'yes_no',
                ],
                'can_access_design' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_design'
                    ]
                ],
                'can_admin_design' => [
                    'type' => 'yes_no',
                ],
                'can_access_addons' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_addons'
                    ]
                ],
                'can_admin_addons' => [
                    'type' => 'yes_no',
                ],
                'can_access_utilities' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_utilities'
                    ]
                ],
                'can_access_logs' => [
                    'type' => 'yes_no',
                ],
                'can_access_sys_prefs' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_sys_prefs'
                    ]
                ],
                'can_access_security_settings' => [
                    'type' => 'yes_no',
                ],
                'can_manage_consents' => [
                    'type' => 'yes_no',
                ],
                'can_view_profiles' => [
                    'type' => 'yes_no',
                ],
                'can_delete_self' => [
                    'type' => 'yes_no',
                ],
                'can_post_comments' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_post_comments'
                    ]
                ],
                'can_search' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_search'
                    ]
                ],
                'can_send_private_messages' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_pms'
                    ]
                ],
                'can_attach_in_private_messages' => [
                    'type' => 'yes_no',
                ],
                'can_send_bulletins' => [
                    'type' => 'yes_no',
                ],
                'can_access_cp' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_cp'
                    ]
                ],
                'can_access_dock' => [
                    'type' => 'yes_no',
                    'group_toggle' => [
                        'y' => 'can_access_dock'
                    ]
                ],
                'can_view_homepage_news' => [
                    'type' => 'yes_no',
                ],
            ],
            'choices' => [
                'website_access' => [
                    'can_view_online_system' => lang('can_view_online_system'),
                    'can_view_offline_system' => lang('can_view_offline_system')
                ],
                'comment_actions' => [
                    'can_moderate_comments' => lang('can_moderate_comments'),
                    'can_edit_own_comments' => lang('can_edit_own_comments'),
                    'can_delete_own_comments' => lang('can_delete_own_comments'),
                    'can_edit_all_comments' => lang('can_edit_all_comments'),
                    'can_delete_all_comments' => lang('can_delete_all_comments')
                ],
                'footer_helper_links' => [
                    'can_access_footer_report_bug' => lang('report_bug'),
                    'can_access_footer_new_ticket' => lang('new_ticket'),
                    'can_access_footer_user_guide' => lang('user_guide'),
                ],
                'channel_permissions' => [
                    'can_create_channels' => lang('create_channels'),
                    'can_edit_channels' => lang('edit_channels'),
                    'can_delete_channels' => lang('delete_channels')
                ],
                'channel_field_permissions' => [
                    'can_create_channel_fields' => lang('create_channel_fields'),
                    'can_edit_channel_fields' => lang('edit_channel_fields'),
                    'can_delete_channel_fields' => lang('delete_channel_fields')
                ],
                'channel_category_permissions' => [
                    'can_create_categories' => lang('create_categories'),
                    'can_edit_categories' => lang('edit_categories'),
                    'can_delete_categories' => lang('delete_categories')
                ],
                'channel_status_permissions' => [
                    'can_create_statuses' => lang('create_statuses'),
                    'can_edit_statuses' => lang('edit_statuses'),
                    'can_delete_statuses' => lang('delete_statuses')
                ],
                'file_upload_directories' => [
                    'can_create_upload_directories' => lang('create_upload_directories'),
                    'can_edit_upload_directories' => lang('edit_upload_directories'),
                    'can_delete_upload_directories' => lang('delete_upload_directories'),
                ],
                'files' => [
                    'can_upload_new_files' => lang('upload_new_files'),
                    'can_edit_files' => lang('edit_files'),
                    'can_delete_files' => lang('delete_files'),
                ],
                'role_actions' => [
                    'can_create_roles' => lang('create_roles'),
                    'can_edit_roles' => lang('edit_roles'),
                    'can_delete_roles' => lang('delete_roles'),
                ],
                'member_actions' => [
                    'can_create_members' => lang('create_members'),
                    'can_edit_members' => lang('edit_members'),
                    'can_delete_members' => lang('can_delete_members'),
                    'can_ban_users' => lang('can_ban_users'),
                    'can_edit_member_fields' => lang('edit_member_fields'),
                    'can_email_from_profile' => lang('can_email_from_profile'),
                    'can_edit_html_buttons' => lang('can_edit_html_buttons')
                ],
                'template_group_permissions' => [
                    'can_create_template_groups' => lang('create_template_groups'),
                    'can_edit_template_groups' => lang('edit_template_groups'),
                    'can_delete_template_groups' => lang('delete_template_groups'),
                ],
                'template_partials' => [
                    'can_create_template_partials' => lang('create_template_partials'),
                    'can_edit_template_partials' => lang('edit_template_partials'),
                    'can_delete_template_partials' => lang('delete_template_partials'),
                ],
                'template_variables' => [
                    'can_create_template_variables' => lang('create_template_variables'),
                    'can_edit_template_variables' => lang('edit_template_variables'),
                    'can_delete_template_variables' => lang('delete_template_variables'),
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

        if ($role) {
            foreach ($permissions['fields'] as $field => $data) {
                $permissions['fields'][$field]['value'] = $role->has($field);
            }

            foreach ($permissions['choices'] as $group => $choices) {
                $permissions['values'][$group] = $this->getPermissionValues($role, $choices);
            }

            if ($role->isNew()) {
                $permissions['values']['website_access'] = ['can_view_online_system'];
            }
        }

        return $permissions;
    }

    private function getPermissionKeys()
    {
        $permissions = $this->getPermissions();

        $all_perms = array_keys($permissions['fields']);

        foreach ($permissions['choices'] as $key => $values) {
            $all_perms = array_merge($all_perms, array_keys($values));
        }

        $channels = ee('Model')->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all()
            ->getDictionary('channel_id', 'channel_title');
        $channels = $this->getChannelAccess($channels);

        foreach ($channels['choices'] as $key => $values) {
            $all_perms = array_merge($all_perms, array_keys($values['children']));
        }

        $templateGroups = $this->getTemplateGroupAccess();

        foreach ($templateGroups['choices'] as $key => $values) {
            $all_perms = array_merge($all_perms, array_keys($values['children']));
        }

        return $all_perms;
    }

    private function getPermissionValues(Role $role, $choices)
    {
        $values = [];

        foreach ($choices as $perm => $data) {
            if ($role->has($perm)) {
                $values[] = $perm;
            }

            // Nested choices
            if (is_array($data) && isset($data['children'])) {
                $values = array_merge($values, $this->getPermissionValues($role, $data['children']));
            }
        }

        return $values;
    }

    private function remove($role_ids)
    {
        if (! ee('Permission')->can('delete_roles')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($role_ids)) {
            $role_ids = array($role_ids);
        }

        //TODO - this needs to be moved to model
        //not all roles can be removed
        $restricted = $this->getRestrictedRoles();
        $need_to_stay = [];
        foreach ($role_ids as $i => $role_id) {
            if (in_array($role_id, $restricted)) {
                $need_to_stay[] = $role_id;
                unset($role_ids[$i]);
            }
        }

        if (!empty($need_to_stay)) {
            $role_names = ee('Model')->get('Role', $need_to_stay)->all()->pluck('name');

            ee('CP/Alert')->makeInline('roles-error')
                ->asWarning()
                ->withTitle(lang('roles_delete_error'))
                ->addToBody(lang('roles_not_deleted_desc'))
                ->addToBody($role_names)
                ->defer();

            return false;
        }

        $replacement_role_id = ee()->input->post('replacement');
        if ($replacement_role_id == 'delete') {
            $replacement_role_id = null;
        }

        if (!empty($replacement_role_id)) {
            $allowed_roles = ee('Model')->get('Role')
                ->filter('role_id', 'NOT IN', array_merge($role_ids, [1,2, 3, 4]));
            if (!ee('Permission')->isSuperAdmin()) {
                $allowed_roles->filter('is_locked', 'n');
            }
            if (!in_array($replacement_role_id, $allowed_roles->all()->pluck('role_id')) || ee('Model')->get('Role', $replacement_role_id)->first() === null) {
                ee('CP/Alert')->makeInline('roles-error')
                    ->asIssue()
                    ->withTitle(lang('roles_delete_error'))
                    ->addToBody(lang('invalid_new_primary_role'))
                    ->defer();

                return false;
            }
        }

        if (!empty($role_ids)) {
            foreach ($role_ids as $role_id) {
                if (ee()->input->post('replacement') == 'delete') {
                    if (!ee('Permission')->can('delete_members')) {
                        show_error(lang('unauthorized_access'), 403);
                    }
                    ee('Model')->get('Member')->filter('role_id', $role_id)->delete();
                } elseif (!empty($replacement_role_id)) {
                    // Query builder for speed
                    ee('db')->where('role_id', $role_id)->update('members', ['role_id' => $replacement_role_id]);
                }
            }

            $roles = ee('Model')->get('Role', $role_ids)->all();
            $role_names = $roles->pluck('name');
            $roles->delete();

            ee('CP/Alert')->makeInline('roles')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody(lang('roles_deleted_desc'))
                ->addToBody($role_names)
                ->defer();

            foreach ($role_names as $role_name) {
                ee()->logger->log_action(sprintf(lang('removed_role'), '<b>' . $role_name . '</b>'));
            }
        }
    }

    /**
     * Delete member role confirm
     *
     * Warning message shown when you try to delete a role
     *
     * @return  mixed
     */
    public function confirm()
    {
        //  Only super admins can delete member groups
        if (! ee('Permission')->can('delete_roles')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $roles = ee()->input->post('selection');

        $roleModels = ee('Model')->get('Role', $roles)->all();
        $vars['members_count_primary'] = 0;
        $vars['members_count_secondary'] = 0;
        foreach ($roleModels as $role) {
            $vars['members_count_primary'] += $role->getMembersCount('primary');
            $vars['members_count_secondary'] += $role->getMembersCount('secondary');
        }

        $vars['new_roles'] = [];
        if (ee('Permission')->can('delete_members')) {
            $vars['new_roles']['delete'] = lang('member_assignment_none');
        }
        $allowed_roles = ee('Model')->get('Role')
            ->fields('role_id', 'name')
            ->filter('role_id', 'NOT IN', array_merge($roles, [1, 2, 3, 4]))
            ->order('name');
        if (! ee('Permission')->isSuperAdmin()) {
            $allowed_roles->filter('is_locked', 'n');
        }
        $vars['new_roles'] += $allowed_roles->all()
            ->getDictionary('role_id', 'name');

        ee()->cp->render('members/delete_member_group_conf', $vars);
    }

    private function getRestrictedRoles()
    {
        $restricted = [1, 2, 3, 4, ee()->config->item('default_primary_role'), ee()->session->getMember()->role_id];
        if (! ee('Permission')->isSuperAdmin()) {
            $restricted = array_merge($restricted, ee('Model')->get('Role')->filter('is_locked', 'y')->all()->pluck('role_id'));
        }

        return $restricted;
    }
}

// EOF
