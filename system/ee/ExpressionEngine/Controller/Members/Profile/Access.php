<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

/**
 * Access Controller
 */
class Access extends Profile
{
    private $base_url = 'members/profile/access';
    private $role_ids;
    private $keyword;

    public function index()
    {
        $filters = $this->makeFilters();
        $values = $filters->values();

        $this->role_ids = ($values['role']) ?: $this->member->getAllRoles()->pluck('role_id');
        $this->keyword = $values['filter_by_keyword'];

        $data = [];

        foreach ($this->getPermissionKeys() as $section => $keys) {
            $data[$section] = [];
            foreach ($keys as $key => $value) {
                if (is_string($value)) {
                    $datum = $this->getAccessRow($value);

                    if (! empty($datum)) {
                        $data[$section][] = $datum;
                    }
                } elseif (is_array($value)) {
                    $datum = $this->getAccessRow($key);
                    if (! empty($datum)) {
                        $data[$section][] = $datum;
                    }

                    foreach ($value as $key) {
                        $datum = $this->getAccessRow($key);

                        if (! empty($datum)) {
                            $datum['nested'] = true;
                            $data[$section][] = $datum;
                        }
                    }
                }
            }
        }

        ee('CP/Alert')->makeInline('access')
            ->asWarning()
            ->cannotClose()
            ->withTitle(lang('important'))
            ->addToBody(sprintf(lang('permissions_granted'), $this->member->getMemberName()))
            ->addToBody(lang('access_privilege_caution'), 'txt-caution')
            ->now();

        $vars = [
            'data' => $data,
            'cp_page_title' => lang('access_overview'),
            'filters' => $filters->render(ee('CP/URL')->make($this->base_url, $this->query_string)),
            'base_url' => ee('CP/URL')->make($this->base_url, $this->query_string),
        ];

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => lang('access_overview')
        ]);

        ee()->cp->render('members/access', $vars);
    }

    protected function makeFilters()
    {
        $filters = ee('CP/Filter');

        $role_ids = $this->member->getAllRoles()->pluck('role_id');

        $role_filter_values = ee('Model')->get('Role', $role_ids)
            ->order('name')
            ->all()
            ->getDictionary('role_id', 'name');

        $role_filter = $filters->make('role', 'role_filter', $role_filter_values)
            ->setPlaceholder(lang('all'))
            ->disableCustomValue();

        $filters->add($role_filter)
            ->add('Keyword');

        return $filters;
    }

    protected function getPermissions()
    {
        static $permissions = [];

        if (empty($permissions)) {
            $primary_icon = ' <sup class="icon--primary" title="' . lang('primary_role') . '"></sup>';

            $allowed = ee('Model')->get('Permission')
                ->with('Role')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('role_id', 'IN', $this->role_ids)
                ->order('Role.name')
                ->all();

            foreach ($allowed as $perm) {
                if (! $this->member->isSuperAdmin() && ! array_key_exists($perm->permission, $permissions)) {
                    $permissions[$perm->permission] = [];
                }

                $display = ee('Format')->make('Text', $perm->Role->name)->convertToEntities();

                if ($perm->Role->getId() == $this->member->role_id) {
                    $display .= $primary_icon;
                }

                $permissions[$perm->permission][] = $display;
            }

            $roles = $this->member->getAllRoles();

            foreach ($roles as $role) {
                $display = ee('Format')->make('Text', $role->name)->convertToEntities();

                if ($role->getId() == $this->member->role_id) {
                    $display .= $primary_icon;
                }

                foreach ($role->AssignedChannels as $channel) {
                    $key = 'access_to_channel_id_' . $channel->getId() . ':' . $channel->channel_title;

                    if (! array_key_exists($key, $permissions)) {
                        $permissions[$key] = [];
                    }

                    $permissions[$key][] = $display;
                }

                foreach ($role->AssignedTemplateGroups as $template_group) {
                    $key = 'access_to_template_group_id_' . $template_group->getId() . ':' . $template_group->group_name;

                    if (! array_key_exists($key, $permissions)) {
                        $permissions[$key] = [];
                    }

                    $permissions[$key][] = $display;
                }

                foreach ($role->AssignedModules as $module) {
                    $addon = ee('Addon')->get(strtolower($module->module_name));
                    if ($addon) {
                        $key = 'access_to_add_on_id_' . $module->getId() . ':' . $addon->getName(); 

                        if (! array_key_exists($key, $permissions)) {
                            $permissions[$key] = [];
                        }

                        $permissions[$key][] = $display;
                    }
                }
            }
        }

        return $permissions;
    }

    protected function getAccessRow($permission)
    {
        $desc = $this->getPermissionDescription($permission);

        if ($this->keyword) {
            if (strpos($desc, $this->keyword) === false) {
                return [];
            }
        }

        $data = [
            'caution' => $this->isTrustedPermission($permission),
            'permission' => $desc,
            'access' => false,
            'granted' => '-'
        ];

        $permissions = $this->getPermissions();

        if ($this->member->isSuperAdmin() && !array_key_exists($permission, $permissions)) {
            $display = ee('Format')->make('Text', 'Super Admin')->convertToEntities();

            if (1 == $this->member->role_id) {
                $display .= ' <sup class="icon--primary" title="' . lang('primary_role') . '"></sup>';
            }
            $permissions[$permission][] = $display;
        }

        if ($this->member->isSuperAdmin() || array_key_exists($permission, $permissions)) {
            $data['access'] = true;
            $data['granted'] = $permissions[$permission];
        }

        return $data;
    }

    protected function getPermissionDescription($permission)
    {
        $key = $permission;
        $name = '';

        foreach (['_channel_id_', '_template_group_id_', '_add_on_id_'] as $delim) {
            if (strpos($permission, $delim)) {
                list($key, $id) = explode($delim, $permission);
                if ($key == 'access_to') {
                    list($id, $name) = explode(':', $id);
                }
            }
        }

        $description = ($key == 'access_to') ? sprintf(lang($key), $name) : lang('access_overview_' . $key);

        return $description;
    }

    protected function getPermissionKeys()
    {
        $permissions = [
            'access_permissions' => [
                'can_view_online_system',
                'can_view_offline_system',
                'can_view_profiles',
                'can_delete_self',
            ],
            'comments' => [
                'can_post_comments',
                'can_moderate_comments',
                'can_edit_own_comments',
                'can_delete_own_comments',
                'can_edit_all_comments',
                'can_delete_all_comments',
            ],
            'search' => [
                'can_search',
            ],
            'personal_messaging' => [
                'can_send_private_messages' => [
                    'can_attach_in_private_messages',
                ],
                'can_send_bulletins',
            ],
            'control_panel' => [
                'can_access_cp',
                'can_access_dock',
                'can_access_footer_report_bug',
                'can_access_footer_new_ticket',
                'can_access_footer_user_guide',
                'can_view_homepage_news',
            ],
            'channel_manager' => [
                'can_admin_channels' => [
                    'can_create_channels',
                    'can_edit_channels',
                    'can_delete_channels',
                    'can_create_channel_fields',
                    'can_edit_channel_fields',
                    'can_delete_channel_fields',
                    'can_create_categories',
                    'can_edit_categories',
                    'can_delete_categories',
                    'can_create_statuses',
                    'can_edit_statuses',
                    'can_delete_statuses',
                ],
            ],
            'file_manager' => [
                'can_access_files' => [
                    'can_create_upload_directories',
                    'can_edit_upload_directories',
                    'can_delete_upload_directories',
                    'can_upload_new_files',
                    'can_edit_files',
                    'can_delete_files',
                ],
            ],
            'member_manager' => [
                'can_access_members' => [
                    'can_create_members',
                    'can_edit_members',
                    'can_delete_members',
                    'can_edit_member_fields',
                    'can_ban_users',
                    'can_email_from_profile',
                    'can_edit_html_buttons',
                ],
                'can_admin_roles' => [
                    'can_create_roles',
                    'can_edit_roles',
                    'can_delete_roles',
                ]
            ],
            'template_manager' => [
                'can_access_design' => [
                    'can_admin_design',
                    'can_create_template_groups',
                    'can_edit_template_groups',
                    'can_delete_template_groups',
                    'can_create_template_partials',
                    'can_edit_template_partials',
                    'can_delete_template_partials',
                    'can_create_template_variables',
                    'can_edit_template_variables',
                    'can_delete_template_variables',
                ],
            ],
            'add_on_manager' => [
                'can_access_addons',
                'can_admin_addons',
            ],
            'utilities' => [
                'can_access_utilities',
                'can_access_comm' => [
                    'can_email_roles',
                    'can_send_cached_email',
                ],
                'can_access_translate',
                'can_access_import',
                'can_access_sql_manager',
                'can_access_data',
            ],
            'logs' => [
                'can_access_logs',
            ],
            'settings' => [
                'can_access_sys_prefs',
                'can_access_security_settings',
                'can_manage_consents',
            ],
        ];

        // Per-Channel Permissons
        $channels = ee('Model')->get('Channel')
            ->fields('channel_id', 'channel_title')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_name')
            ->all();

        foreach ($channels as $channel) {
            $key = 'access_to_channel_id_' . $channel->getId() . ':' . $channel->channel_title;
            $permissions['channel_manager'][$key] = [
                'can_create_entries_channel_id_' . $channel->getId(),
                'can_edit_self_entries_channel_id_' . $channel->getId(),
                'can_delete_self_entries_channel_id_' . $channel->getId(),
                'can_edit_other_entries_channel_id_' . $channel->getId(),
                'can_delete_all_entries_channel_id_' . $channel->getId(),
                'can_assign_post_authors_channel_id_' . $channel->getId(),
            ];
        }

        // Per-Template Group Permissions
        $template_groups = ee('Model')->get('TemplateGroup')
            ->fields('group_id', 'group_name')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name')
            ->all();

        foreach ($template_groups as $group) {
            $key = 'access_to_template_group_id_' . $group->getId() . ':' . $group->group_name;
            $permissions['template_manager'][$key] = [
                'can_create_templates_template_group_id_' . $group->getId(),
                'can_edit_templates_template_group_id_' . $group->getId(),
                'can_delete_templates_template_group_id_' . $group->getId(),
                'can_manage_settings_template_group_id_' . $group->getId(),
            ];
        }

        // Add-on access
        $addons = ee('Model')->get('Module')
            ->fields('module_id', 'module_name')
            ->filter('module_name', 'NOT IN', array('Channel', 'Comment', 'Member', 'File', 'Filepicker')) // @TODO This REALLY needs abstracting.
            ->all()
            ->filter(function ($addon) {
                $provision = ee('Addon')->get(strtolower($addon->module_name));

                if (! $provision) {
                    return false;
                }

                $addon->module_name = $provision->getName();

                return true;
            })
            ->getDictionary('module_id', 'module_name');

        foreach ($addons as $id => $name) {
            $permissions['add_on_manager'][] = 'access_to_add_on_id_' . $id . ':' . $name;
        }

        $permissions['add_on_manager'][] = 'can_upload_new_toolsets';
        $permissions['add_on_manager'][] = 'can_edit_toolsets';
        $permissions['add_on_manager'][] = 'can_delete_toolsets';

        return $permissions;
    }

    protected function isTrustedPermission($permission)
    {
        $keys = [
            'can_edit_own_comments',
            'can_delete_own_comments',
            'can_edit_all_comments',
            'can_delete_all_comments',
            'can_access_cp',
            'can_access_dock',
            'can_admin_channels',
            'can_create_members',
            'can_edit_members',
            'can_delete_members',
            'can_ban_users',
            'can_email_from_profile',
            'can_edit_html_buttons',
            'can_admin_roles',
            'can_create_roles',
            'can_edit_roles',
            'can_delete_roles',
            'can_admin_design',
            'can_create_template_groups',
            'can_edit_template_groups',
            'can_delete_template_groups',
            'can_create_template_partials',
            'can_edit_template_partials',
            'can_delete_template_partials',
            'can_create_template_variables',
            'can_edit_template_variables',
            'can_delete_template_variables',
            'can_admin_addons',
            'can_access_comm',
            'can_email_roles',
            'can_send_cached_email',
            'can_access_translate',
            'can_access_import',
            'can_access_sql_manager',
            'can_access_data',
            'can_access_sys_prefs',
            'can_access_security_settings',
            'can_manage_consents',
        ];

        if (in_array($permission, $keys)) {
            return true;
        }

        if (strpos($permission, '_channel_id_') ||
            strpos($permission, '_template_group_id_') ||
            strpos($permission, '_add_on_id_')) {
            return true;
        }

        return false;
    }
}
// END CLASS

// EOF
