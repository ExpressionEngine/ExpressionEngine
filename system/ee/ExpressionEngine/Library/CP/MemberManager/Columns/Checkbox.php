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

/**
 * Checkbox Column
 */
class Checkbox extends EntryManager\Columns\Checkbox
{
    public function renderTableCell($data, $field_id, $member)
    {
        if (ee('Permission')->isSuperAdmin() || $member->member_id == ee()->session->userdata('member_id')) {
            $canEdit = true;
        } else {
            $canEdit = (bool) ($member->PrimaryRole->is_locked != 'y' && (ee('Permission')->can('edit_members') || ee('Permission')->can('delete_members')));
        }
        $data = [
            'name' => 'selection[]',
            'value' => $member->getId(),
            'disabled' => ! $canEdit,
            'data' => [
                'confirm' => lang('member') . ': <b>' . htmlentities(!empty($member->screen_name) ? $member->screen_name : $member->username, ENT_QUOTES, 'UTF-8') . '</b>'
            ]
        ];

        return $data;
    }

    public function getEntryManagerColumnSortField()
    {
        return 'member_id';
    }
}
