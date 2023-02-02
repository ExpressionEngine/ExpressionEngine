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
use ExpressionEngine\Model\Role\RoleGroup;

/**
 * Members\Roles\Groups Controller
*/
class Groups extends AbstractRolesController
{
    public function create()
    {
        if (!ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->generateSidebar();

        $vars = array(
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('members/roles/groups/create'),
            'sections' => $this->form(),
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
            ]
        );

        if (AJAX_REQUEST) {
            unset($vars['buttons'][2]);
        }

        if (! empty($_POST)) {
            $role_group = $this->setWithPost(ee('Model')->make('RoleGroup'));
            $result = $role_group->validate();

            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $role_group->save();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('create_role_group_success'))
                    ->addToBody(sprintf(lang('create_role_group_success_desc'), $role_group->name))
                    ->defer();

                if (AJAX_REQUEST) {
                    return ['saveId' => $role_group->getId()];
                }

                if (ee('Request')->post('submit') == 'save_and_new') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/groups/create'));
                } elseif (ee('Request')->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/groups'));
                } else {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/groups/edit/' . $role_group->getId()));
                }
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('create_role_group_error'))
                    ->addToBody(lang('create_role_group_error_desc'))
                    ->now();
            }
        }

        ee()->view->cp_page_title = lang('create_role_group');

        if (AJAX_REQUEST) {
            return ee()->cp->render('_shared/form', $vars);
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/roles')->compile() => lang('roles'),
            '' => lang('create_role_group')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function edit($id)
    {
        if (!ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        $role_group = ee('Model')->get('RoleGroup', $id)->first();

        if (! $role_group) {
            show_404();
        }

        $this->generateSidebar($id);

        $vars = array(
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('members/roles/groups/edit/' . $id),
            'sections' => $this->form($role_group),
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
            ]
        );

        if (! empty($_POST)) {
            $role_group = $this->setWithPost($role_group);
            $result = $role_group->validate();

            if ($response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $role_group->save();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('edit_role_group_success'))
                    ->addToBody(sprintf(lang('edit_role_group_success_desc'), $role_group->name))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/groups/create'));
                } elseif (ee('Request')->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles'));
                } else {
                    ee()->functions->redirect(ee('CP/URL')->make('members/roles/groups/edit/' . $role_group->getId()));
                }
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('edit_role_group_error'))
                    ->addToBody(lang('edit_role_group_error_desc'))
                    ->now();
            }
        }

        ee()->view->cp_page_title = lang('edit_role_group');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/roles')->compile() => lang('roles'),
            '' => lang('edit_role_group')
        );

        ee()->cp->render('settings/form', $vars);
    }

    private function setWithPost(RoleGroup $role_group)
    {
        $role_group->set($_POST);
        $role_group->Roles = ee('Model')->get('Role', ee('Request')->post('roles'))->all();

        return $role_group;
    }

    private function form(RoleGroup $role_group = null)
    {
        if (! $role_group) {
            $role_group = ee('Model')->make('RoleGroup');
            $role_group->Roles = null;
        }

        $roles = ee('Model')->get('Role')
            ->fields('role_id', 'name')
            ->order('name')
            ->all()
            ->getDictionary('role_id', 'name');

        $sections = [
            [
                [
                    'title' => 'name',
                    'desc' => '',
                    'fields' => [
                        'name' => [
                            'type' => 'text',
                            'value' => $role_group->name,
                            'required' => true
                        ]
                    ]
                ],
                [
                    'title' => 'roles',
                    'desc' => 'group_roles_desc',
                    'fields' => [
                        'roles' => [
                            'type' => 'checkbox',
                            'value' => $role_group->Roles->pluck('role_id'),
                            'choices' => $roles
                        ]
                    ]
                ],
            ]
        ];

        return $sections;
    }

    public function remove()
    {
        if (!ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        $group_id = ee('Request')->post('content_id');

        $role_groups = ee('Model')->get('RoleGroup', $group_id)->all();

        $names = $role_groups->pluck('name');

        $role_groups->delete();
        ee('CP/Alert')->makeInline('field-groups')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('role_groups_removed_desc'))
            ->addToBody($names)
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make('members/roles', ee()->cp->get_url_state()));
    }
}

// EOF
