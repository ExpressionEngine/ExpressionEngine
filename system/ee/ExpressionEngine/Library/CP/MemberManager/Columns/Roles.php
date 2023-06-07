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

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Member\Member;

/**
 * Roles Column
 */
class Roles extends Column
{
    public function getEntryManagerColumnModels()
    {
        return ['Roles'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['Roles.name'];
    }

    public function getTableColumnLabel()
    {
        return 'roles';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_INFO,
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $member)
    {
        $primary_icon = ' <sup class="icon--primary" title="' . lang('primary_role') . '"></sup>';

        switch ($member->PrimaryRole->getId()) {
            case Member::BANNED:
                $group = "<span class='st-banned'>" . lang('banned') . "</span>";
                $attrs['class'] = 'banned';

                break;
            case Member::PENDING:
                $group = "<span class='st-pending'>" . lang('pending') . "</span>";
                $attrs['class'] = 'pending';

                if (ee('Permission')->can('edit_members')) {
                    $group .= "<a class=\"success-link icon-right button button--small button--default\" href=\"" . ee('CP/URL')->make('members/approve/' . $member->member_id) . "\" title=\"" . lang('approve') . "\"><i class=\"fal fa-check\"><span class=\"hidden\">" . lang('approve') . "</span></i></a>";
                }

                break;
            default:
                $group = $member->PrimaryRole->name . $primary_icon;
        }

        foreach ($member->getAllRoles() as $role) {
            if ($role->getId() != 0 && $role->getId() != $member->role_id) {
                $group .= ', ' . $role->name;
            }
        }

        return $group;
    }
}
