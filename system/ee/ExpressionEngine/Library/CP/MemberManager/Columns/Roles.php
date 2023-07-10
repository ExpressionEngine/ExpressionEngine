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
        $out = '';
        $roles = $member->getAllRoles();
        foreach ($roles as $role) {
            $label = $role->name;
            if ($role->getId() == Member::PENDING) {
                $pendingRoleId = ($member->pending_role_id != 0) ? $member->pending_role_id : ee()->config->item('default_primary_role');
                $pendingRole = ee('Model')->get('Role', $pendingRoleId)->first(true);
                $label = $pendingRole->name;
                $label .= ' <sup><i class="fal fa-hourglass-start" title="' . lang('pending') . '"></i></sup>';
            } elseif (count($roles) > 1 && $role->getId() == $member->role_id) {
                $label .= ' <sup><i class="fas fa-user-check" title="' . lang('primary_role') . '"></i></sup>';
            }
            $vars = [
                'label' => $label,
                'class' => str_replace(' ', '_', strtolower($role->name)),
                'styles' => [
                    'background-color' => 'var(--ee-bg-blank)',
                    'border-color' => '#' . $role->highlight,
                    'color' => '#' . $role->highlight,
                ]
            ];
            $out .= ee('View')->make('_shared/status-tag')->render($vars);
            if ($role->getId() == Member::PENDING && ee('Permission')->can('edit_members')) {
                $out .= " <a class=\"success-link icon-right button button--small button--default\" href=\"" . ee('CP/URL')->make('members/approve/' . $member->member_id) . "\" title=\"" . lang('approve') . "\"><i class=\"fal fa-check\"><span class=\"hidden\">" . lang('approve') . "</span></i></a>";
            }
        }

        return $out;
    }
}
