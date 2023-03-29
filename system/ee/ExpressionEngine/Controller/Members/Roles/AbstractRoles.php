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

use CP_Controller;

/**
 * Abstract Roles
 */
abstract class AbstractRoles extends CP_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if ((! ee('Permission')->has('can_access_members')) || (! ee('Permission')->has('can_admin_roles'))) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('admin');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('members');

        $header = [
            'title' => lang('roles_manager')
        ];

        if (ee('Permission')->can('create_roles')) {
            $header['action_button'] = [
                'text' => lang('new_role'),
                'href' => ee('CP/URL')->make('members/roles/create/' . (ee('Request')->get('group_id') ? (int) ee('Request')->get('group_id') : ''))
            ];
        }

        ee()->view->header = $header;

        ee()->cp->add_js_script(array(
            'file' => array('cp/members/roles/menu'),
        ));
    }

    protected function generateSidebar($active = null)
    {
        // More than one group can be active, so we use an array
        $active_groups = (is_array($active)) ? $active : array($active);

        $sidebar = ee('CP/Sidebar')->makeNew();

        $all_roles = $sidebar->addItem(lang('all_roles'), ee('CP/URL')->make('members/roles'))->withIcon('user-tag');

        if ($active) {
            $all_roles->isInactive();
        }

        if (ee('Permission')->isSuperAdmin()) {
            $header = $sidebar->addHeader(lang('role_groups'));

            $list = $header->addFolderList('role_groups')
                ->withNoResultsText(sprintf(lang('no_found'), lang('role_groups')));

            if (ee('Permission')->can('delete_roles')) {
                $list->withRemoveUrl(ee('CP/URL')->make('members/roles/groups/remove', ee()->cp->get_url_state()))
                    ->withRemovalKey('content_id');
            }

            $imported_groups = ee()->session->flashdata('imported_role_groups') ?: [];

            $role_groups = ee('Model')->get('RoleGroup')
                ->order('name')
                ->all();

            foreach ($role_groups as $group) {
                $name = ee('Format')->make('Text', $group->name)->convertToEntities();

                $item = $list->addItem(
                    $name,
                    ee('CP/URL')->make('members/roles', ['group_id' => $group->getId()])
                );

                if (ee('Permission')->can('edit_roles')) {
                    $item->withEditUrl(
                        ee('CP/URL')->make('members/roles/groups/edit/' . $group->getId())
                    );
                }

                if (ee('Permission')->can('delete_roles')) {
                    $item->withRemoveConfirmation(
                        lang('role_group') . ': <b>' . $name . '</b>'
                    )->identifiedBy($group->getId());
                }

                if (in_array($group->getId(), $active_groups)) {
                    $item->isActive();
                } else {
                    $item->isInactive();
                }

                if (in_array($group->getId(), $imported_groups)) {
                    $item->isSelected();
                }
            }

            $header->withButton(lang('new'), ee('CP/URL')->make('members/roles/groups/create'));
        }

        ee()->view->left_nav = $sidebar->render();
        ee()->view->left_nav_collapsed = $sidebar->collapsedState;
    }
}

// EOF
