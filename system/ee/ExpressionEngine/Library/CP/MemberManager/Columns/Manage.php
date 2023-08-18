<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\MemberManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Member\Member;

/**
 * Manage Column
 */
class Manage extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return '';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_TOOLBAR,
        ];
    }

    public function renderTableCell($data, $field_id, $member)
    {
        if (ee('Permission')->isSuperAdmin() || $member->member_id == ee()->session->userdata('member_id')) {
            $canEdit = true;
        } else {
            $canEdit = (bool) ($member->PrimaryRole->is_locked != 'y' && (ee('Permission')->can('edit_members') || ee('Permission')->can('delete_members')));
        }
        if (!$canEdit) {
            return [
                'toolbar_items' => []
            ];
        }

        $toolbar = [];
        //if (! ee('Permission')->can('ban_users')) {
        if ($member->role_id == Member::PENDING) {
            $toolbar['approve'] = array(
                'href' => ee('CP/URL')->make('files/directory/' . $member->upload_location_id, ['directory_id' => $member->getId()]),
                'title' => lang('approve'),
            );
            if (ee()->config->item('req_mbr_activation') !== 'email' && ee('Permission')->can('edit_members')) {
                $toolbar['resend'] = array(
                    'href' => '#',
                    'title' => lang('resend'),
                    'rel' => 'modal-confirm-rename-file',
                    'class' => 'm-link',
                    'data-file-id' => $member->getId(),
                );
            }
            if (ee('Permission')->has('can_delete_members')) {
                $toolbar['decline'] = array(
                    'href' => '',
                    'class' => 'm-link with-divider',
                    'rel' => 'modal-confirm-decline',
                    'data-file-id' => $member->getId(),
                    'title' => lang('decline'),
                );
            }
        } else {
            if (ee('Permission')->has('can_edit_members')) {
                $toolbar['edit'] = array(
                    'href' => ee('CP/URL')->make('members/profile/settings', ['id' => $member->getId()]),
                    'class' => '',
                    'title' => lang('edit')
                );
                $toolbar['roles'] = array(
                    'href' => ee('CP/URL')->make('members/profile/roles', ['id' => $member->getId()]),
                    'title' => lang('roles')
                );
            }
            if (ee('Permission')->isSuperAdmin() && $member->member_id != ee()->session->userdata('member_id')) {
                $toolbar['login_as'] = array(
                    'href' => ee('CP/URL')->make('members/profile/login', ['id' => $member->getId()]),
                    'title' => lang('login_as_member')
                );
            }
            if (ee('Permission')->has('can_delete_members') && $member->member_id != ee()->session->userdata('member_id')) {
                $toolbar['delete'] = [
                    'href' => '',
                    'class' => 'm-link with-divider',
                    'rel' => 'modal-confirm-delete',
                    'data-file-id' => $member->getId(),
                    'data-confirm-ajax' => ee('CP/URL')->make('members/confirm')->compile(),
                    'title' => lang('delete'),
                ];
            }
        }

        return [
            'toolbar_items' => $toolbar,
            'toolbar_type' => 'dropdown',
        ];
    }
}
