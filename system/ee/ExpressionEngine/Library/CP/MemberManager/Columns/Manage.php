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
        $confirmationUrl = ee('CP/URL')->make('files/confirm')->compile();
        $toolbar = [];
        //if (! ee('Permission')->can('ban_users')) {
        if ($member->role_id == Member::PENDING) {
            $toolbar['approve'] = array(
                'href' => ee('CP/URL')->make('files/directory/' . $member->upload_location_id, ['directory_id' => $member->getId()]),
                'title' => lang('approve'),
            );
            $toolbar['resend'] = array(
                'href' => '#',
                'title' => lang('resend'),
                'rel' => 'modal-confirm-rename-file',
                'class' => 'm-link',
                'data-file-id' => $member->getId(),
                'data-file-name' => $member->getId(),
                'data-confirm-ajax' => '',
            );
            $toolbar['decline'] = array(
                'href' => '#',
                'title' => lang('decline'),
                'rel' => 'modal-confirm-move-file',
                'data-file-id' => $member->getId(),
                'data-file-name' => $member->getId(),
                'data-confirm-ajax' => '',
            );
        } else {
            $toolbar['edit'] = array(
                'href' => '#',
                'title' => lang('edit'),
                'rel' => 'modal-confirm-move-file',
                'data-file-id' => $member->getId(),
                'data-file-name' => $member->getId(),
                'data-confirm-ajax' => '',
            );
            $toolbar['roles'] = array(
                'href' => '#',
                'title' => 'Manage roles',
                'rel' => 'modal-confirm-move-file',
                'data-file-id' => $member->getId(),
                'data-file-name' => $member->getId(),
                'data-confirm-ajax' => '',
            );
            $toolbar['delete'] = array(
                'href' => '#',
                'title' => lang('delete'),
                'rel' => 'modal-confirm-move-file',
                'data-file-id' => $member->getId(),
                'data-file-name' => $member->getId(),
                'data-confirm-ajax' => '',
            );
            $toolbar['login_as'] = array(
                'href' => '#',
                'title' => 'Log in as',
                'rel' => 'modal-confirm-move-file',
                'data-file-id' => $member->getId(),
                'data-file-name' => $member->getId(),
                'data-confirm-ajax' => '',
            );
        }

        return [
            'toolbar_items' => $toolbar,
            'toolbar_type' => 'dropdown',
        ];
    }
}
