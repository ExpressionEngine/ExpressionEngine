<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Design;

use ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use ExpressionEngine\Model\Template\TemplateGroup as TemplateGroupModel;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Design\Group Controller
 */
class Group extends AbstractDesignController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_design')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->stdHeader();
    }

    public function create()
    {
        if (! ee('Permission')->can('create_template_groups')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $errors = null;

        $groups = array(
            'false' => '-- ' . strtolower(lang('none')) . ' --'
        );
        ee('Model')->get('TemplateGroup')
            ->all()
            ->filter('site_id', ee()->config->item('site_id'))
            ->each(function ($group) use (&$groups) {
                $groups[$group->group_id] = $group->group_name;
                if ($group->is_site_default) {
                    $groups[$group->group_id] .= ' (' . lang('default') . ')';
                }
            });

        // Not a superadmin?  Preselect their member group as allowed to view templates
        $selected_roles = (! ee('Permission')->isSuperAdmin()) ? ee()->session->getMember()->getAllRoles()->pluck('role_id') : array();

        // only roles with design manager access
        $roles = ee('Model')->get('Role', ee('Permission')->rolesThatCan('access_design'))
            ->filter('role_id', 'NOT IN', array(1, 2, 4))
            ->order('name', 'asc')
            ->all();

        $choices = [];
        $values = [];

        foreach ($roles as $role) {
            $choices['role_id_' . $role->getId()] = [
                'label' => $role->name,
                'children' => [
                    'can_create_templates_template_group_id_' . ':role_id_' . $role->getId() => lang('can_create_templates'),
                    'can_edit_templates_template_group_id_' . ':role_id_' . $role->getId() => lang('can_edit_templates'),
                    'can_delete_templates_template_group_id_' . ':role_id_' . $role->getId() => lang('can_delete_templates'),
                    'can_manage_settings_template_group_id_' . ':role_id_' . $role->getId() => lang('can_manage_settings'),
                ]
            ];
        }

        $perms = [
            'can_create_templates_template_group_id_',
            'can_edit_templates_template_group_id_',
            'can_delete_templates_template_group_id_',
            'can_manage_settings_template_group_id_',
        ];

        foreach ($selected_roles as $role_id) {
            $values[] = 'role_id_' . $role_id;

            foreach ($perms as $perm) {
                $values[] = $perm . ':role_id_' . $role_id;
            }
        }

        if (! empty($_POST)) {
            $group = ee('Model')->make('TemplateGroup');
            $group->site_id = ee()->config->item('site_id');

            $result = $this->validateTemplateGroup($group);
            if ($result instanceof ValidationResult) {
                $errors = $result;

                if ($result->isValid()) {
                    $permissions = [];

                    // Only set member groups from post if they have permission to admin member groups and a value is set
                    if (ee()->input->post('roles') && (ee('Permission')->isSuperAdmin() or ee('Permission')->can('admin_roles'))) {
                        $role_ids = [];

                        foreach (ee('Request')->post('roles') as $value) {
                            if (empty($value)) {
                                continue;
                            }

                            if (strpos($value, 'role_id_') === 0) {
                                $role_ids[] = str_replace('role_id_', '', $value);
                            } else {
                                list($permission, $role_id) = explode(':role_id_', $value);

                                $permissions[] = ee('Model')->make('Permission', [
                                    'role_id' => $role_id,
                                    'site_id' => ee()->config->item('site_id'),
                                    'permission' => $permission
                                ]);
                            }
                        }

                        $group->Roles = ee('Model')->get('Role', $role_ids)->all();
                    } elseif (! ee('Permission')->isSuperAdmin() and ! ee('Permission')->can('admin_roles')) {
                        // No permission to admin, so their group is automatically added to the template group
                        $role_id = ee()->session->getMember()->PrimaryRole->getId();
                        $group->Roles = ee('Model')->get('Role', $role_id)->all();

                        foreach ($perms as $perm) {
                            $permissions[] = ee('Model')->make('Permission', [
                                'role_id' => $role_id,
                                'site_id' => ee()->config->item('site_id'),
                                'permission' => $perm
                            ]);
                        }
                    }

                    // Does the current member have permission to access the template group they just created?
                    $redirect_name = (ee('Permission')->isSuperAdmin() or ee('Permission')->hasAnyRole($roles)) ? true : false;

                    $group->save();

                    foreach ($permissions as $perm) {
                        $perm->permission .= $group->getId();
                        $perm->save();
                    }

                    $duplicate = false;

                    if (is_numeric(ee()->input->post('duplicate_group'))) {
                        $master_group = ee('Model')->get('TemplateGroup', ee()->input->post('duplicate_group'))->first();
                        $master_group_templates = $master_group->getTemplates();
                        if (count($master_group_templates) > 0) {
                            $duplicate = true;
                        }
                    }

                    if (! $duplicate) {
                        $template = ee('Model')->make('Template');
                        $template->group_id = $group->group_id;
                        $template->template_name = 'index';
                        $template->template_data = '';
                        $template->last_author_id = 0;
                        $template->edit_date = ee()->localize->now;
                        $template->site_id = ee()->config->item('site_id');
                        $template->Roles = ee('Model')->get('Role')->all();
                        $template->save();
                    } else {
                        foreach ($master_group_templates as $master_template) {
                            $values = $master_template->getValues();
                            unset($values['template_id']);
                            $new_template = ee('Model')->make('Template', $values);
                            $new_template->template_id = null;
                            $new_template->group_id = $group->group_id;
                            $new_template->edit_date = ee()->localize->now;
                            $new_template->site_id = ee()->config->item('site_id');
                            $new_template->hits = 0; // Reset hits
                            $new_template->Roles = $master_template->Roles;
                            if (!ee('Config')->getFile()->getBoolean('allow_php') || !ee('Permission')->isSuperAdmin()) {
                                $new_template->allow_php = false;
                            }
                            $new_template->save();
                        }
                    }

                    // Only redirect to the template group if the member has permission to view it
                    $name = ($redirect_name) ? $group->group_name : '';

                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('create_template_group_success'))
                        ->addToBody(sprintf(lang('create_template_group_success_desc'), $group->group_name))
                        ->defer();

                    ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $name));
                }
            }
        }

        $vars = array(
            'ajax_validate' => true,
            'errors' => $errors,
            'base_url' => ee('CP/URL')->make('design/group/create'),
            'save_btn_text' => sprintf(lang('btn_save'), lang('template_group')),
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(
                array(
                    array(
                        'title' => 'name',
                        'desc' => 'name_desc',
                        'fields' => array(
                            'group_name' => array(
                                'type' => 'text',
                                'required' => true
                            )
                        )
                    ),
                    (sizeof($groups) > 1) ? array(
                        'title' => 'duplicate_group',
                        'desc' => 'duplicate_group_desc',
                        'fields' => array(
                            'duplicate_group' => array(
                                'type' => 'radio',
                                'choices' => $groups,
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('template_groups'))
                                ]
                            )
                        )
                    ) : '',
                    array(
                        'title' => 'make_default_group',
                        'desc' => 'make_default_group_desc',
                        'fields' => array(
                            'is_site_default' => array(
                                'type' => 'yes_no',
                                'value' => ee('Model')->get('TemplateGroup')
                                    ->filter('site_id', ee()->config->item('site_id'))
                                    ->filter('is_site_default', 'y')
                                    ->count() ? 'n' : 'y'
                            )
                        )
                    ),
                    array(
                        'title' => 'template_roles',
                        'desc' => 'template_roles_desc',
                        'fields' => array(
                            'roles' => array(
                                'type' => 'checkbox',
                                'required' => true,
                                'nested' => true,
                                'auto_select_parents' => true,
                                'choices' => $choices,
                                'value' => $values,
                                'no_results' => [
                                    'text' => sprintf(lang('no_roles_with_design_access_found'))
                                ]
                            )
                        )
                    ),
                )
            )
        );

        // Permission check for assigning member groups to templates
        if (! ee('Permission')->can('admin_roles')) {
            unset($vars['sections'][0][3]);
        }

        $this->generateSidebar();
        ee()->view->cp_page_title = lang('create_new_template_group');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            '' => lang('create_new_template_group')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function edit($group_name, $group_id = null)
    {
        if (! ee('Permission')->can('edit_template_groups')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $errors = null;

        $group = ee('Model')->get('TemplateGroup')
            ->filter('group_name', $group_name);
        if (!is_null($group_id) && is_numeric($group_id)) {
            $group = $group->filter('group_id', $group_id);
        }
        $group = $group->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $group) {
            show_error(sprintf(lang('error_no_template_group'), $group_name));
        }

        if (! in_array($group->group_id, $this->assigned_template_groups)) {
            show_error(lang('unauthorized_access'), 403);
        }

        // only roles with design manager access
        $roles = ee('Model')->get('Role', ee('Permission')->rolesThatCan('access_design'))
            ->filter('role_id', 'NOT IN', array(1, 2, 4))
            ->order('name', 'asc')
            ->all();

        $choices = [];
        $values = [];

        foreach ($roles as $role) {
            $choices['role_id_' . $role->getId()] = [
                'label' => $role->name,
                'children' => [
                    'can_create_templates_template_group_id_' . $group->getId() . ':role_id_' . $role->getId() => lang('can_create_templates'),
                    'can_edit_templates_template_group_id_' . $group->getId() . ':role_id_' . $role->getId() => lang('can_edit_templates'),
                    'can_delete_templates_template_group_id_' . $group->getId() . ':role_id_' . $role->getId() => lang('can_delete_templates'),
                    'can_manage_settings_template_group_id_' . $group->getId() . ':role_id_' . $role->getId() => lang('can_manage_settings'),
                ]
            ];
        }

        $perms = [
            'can_create_templates_template_group_id_' . $group->getId(),
            'can_edit_templates_template_group_id_' . $group->getId(),
            'can_delete_templates_template_group_id_' . $group->getId(),
            'can_manage_settings_template_group_id_' . $group->getId(),
        ];

        foreach ($group->Roles as $role) {
            $values[] = 'role_id_' . $role->getId();

            foreach ($perms as $perm) {
                if ($role->has($perm)) {
                    $values[] = $perm . ':role_id_' . $role->getId();
                }
            }
        }

        if (! empty($_POST)) {
            $result = $this->validateTemplateGroup($group);
            if ($result instanceof ValidationResult) {
                $errors = $result;

                if ($result->isValid()) {
                    // On edit, if they don't have permission to edit member group permissions, they can't change
                    // template member group settings
                    if (ee('Permission')->isSuperAdmin() or ee('Permission')->can('admin_roles')) {
                        ee('Model')->get('Permission')
                            ->filter('permission', 'IN', $perms)
                            ->filter('site_id', ee()->config->item('site_id'))
                            ->delete();

                        // If post is null and field should be present, unassign members
                        // If field isn't present, we don't change whatever it's currently set to
                        if (! ee('Request')->post('roles')) {
                            $group->Roles = [];
                        } else {
                            $role_ids = [];
                            $permissions = [];

                            foreach (ee('Request')->post('roles') as $value) {
                                if (empty($value)) {
                                    continue;
                                }

                                if (strpos($value, 'role_id_') === 0) {
                                    $role_ids[] = str_replace('role_id_', '', $value);
                                } else {
                                    list($permission, $role_id) = explode(':role_id_', $value);

                                    ee('Model')->make('Permission', [
                                        'role_id' => $role_id,
                                        'site_id' => ee()->config->item('site_id'),
                                        'permission' => $permission
                                    ])->save();
                                }
                            }

                            $group->Roles = ee('Model')->get('Role', $role_ids)->all();
                        }
                    }

                    // Does the current member have permission to access the template group they just created?
                    $roles = $group->Roles->pluck('role_id');
                    $redirect_name = (ee('Permission')->isSuperAdmin() or ee('Permission')->hasAnyRole($roles)) ? true : false;

                    $group->save();

                    // Only redirect to the template group if the member has permission to view it
                    $name = ($redirect_name) ? $group->group_name : '';

                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('edit_template_group_success'))
                        ->addToBody(sprintf(lang('edit_template_group_success_desc'), $group->group_name))
                        ->defer();

                    ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $name));
                }
            }
        }

        $vars = array(
            'ajax_validate' => true,
            'errors' => $errors,
            'base_url' => ee('CP/URL')->make('design/group/edit/' . $group_name . '/' . $group->group_id),
            'save_btn_text' => sprintf(lang('btn_save'), lang('template_group')),
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(
                array(
                    array(
                        'title' => 'name',
                        'desc' => 'alphadash_desc',
                        'fields' => array(
                            'group_name' => array(
                                'type' => 'text',
                                'required' => true,
                                'value' => $group->group_name
                            )
                        )
                    ),
                    array(
                        'title' => 'make_default_group',
                        'desc' => 'make_default_group_desc',
                        'fields' => array(
                            'is_site_default' => array(
                                'type' => 'yes_no',
                                'value' => $group->is_site_default
                            )
                        )
                    ),
                    array(
                        'title' => 'template_roles',
                        'desc' => 'template_roles_desc',
                        'fields' => array(
                            'roles' => array(
                                'type' => 'checkbox',
                                'required' => true,
                                'nested' => true,
                                'auto_select_parents' => true,
                                'choices' => $choices,
                                'value' => $values,
                                'no_results' => [
                                    'text' => sprintf(lang('no_roles_with_design_access_found'))
                                ]
                            )
                        )
                    ),
                )
            )
        );

        // Permission check for assigning member groups to templates
        if (! ee('Permission')->can('admin_roles')) {
            unset($vars['sections'][0][2]);
        }

        $this->generateSidebar($group->group_id);
        ee()->view->cp_page_title = lang('edit_template_group');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            '' => lang('edit_template_group')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function remove()
    {
        if (! ee('Permission')->can('delete_template_groups')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $groups = ee('Model')->get('TemplateGroup');
        if (is_numeric(ee()->input->post('group_id'))) {
            $groups = $groups->filter('group_id', ee()->input->post('group_id'));
        } else {
            $groups = $groups->filter('group_name', ee()->input->post('group_name'));
        }
        $groups = $groups->filter('site_id', ee()->config->item('site_id'))
            ->all();
        $group = $groups->first();

        if (! $group) {
            show_error(lang('group_not_found'));
        } else {
            if (! in_array($group->group_id, $this->assigned_template_groups)) {
                show_error(lang('unauthorized_access'), 403);
            }

            // Delete the group folder if it exists
            // we only do this if there are no duplicate group names
            if (ee()->config->item('save_tmpl_files') == 'y' && $groups->count() == 1) {
                $basepath = PATH_TMPL;
                $basepath .= ee()->config->item('site_short_name') . '/' . $group->group_name . '.group/';

                ee()->load->helper('file');
                delete_files($basepath, true);
                @rmdir($basepath);
            }

            $group->delete();
            ee('CP/Alert')->makeInline('template-group')
                ->asSuccess()
                ->withTitle(lang('template_group_deleted'))
                ->addToBody(sprintf(lang('template_group_deleted_desc'), $group->group_name))
                ->defer();
        }

        if (ee()->input->post('return')) {
            $return = ee('CP/URL')->make(ee('Security/XSS')->clean(ee('Request')->post('return')));
        } else {
            $return = ee('CP/URL')->make('design');
        }

        ee()->functions->redirect($return);
    }

    /**
     * Sets a template group entity with the POSTed data and validates it, setting
     * an alert if there are any errors.
     *
     * @param TemplateGroupModel $$group A TemplateGroup entity
     * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
     *  or a ValidationResult object.
     */
    private function validateTemplateGroup(TemplateGroupModel $group)
    {
        if (empty($_POST)) {
            return false;
        }

        $group->group_name = ee()->input->post('group_name');
        $group->is_site_default = ee()->input->post('is_site_default');

        $result = $group->validate();

        $field = ee()->input->post('ee_fv_field');

        if ($response = $this->ajaxValidation($result)) {
            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('edit_template_group_error'))
                ->addToBody(lang('edit_template_group_error_desc'))
                ->now();
        }

        return $result;
    }
}

// EOF
