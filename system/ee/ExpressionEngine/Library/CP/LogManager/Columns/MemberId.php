<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\LogManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Library\CP\Table;

/**
 * Member ID Column
 */
class MemberId extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'member';
    }

    public function getEntryManagerColumnModels()
    {
        return ['Member'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['Member.member_id, Member.username, Member.screen_name'];
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_INFO,
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $log)
    {
        if (empty($log->member_id)) {
            return '';
        }
        if (ee('Permission')->isSuperAdmin() || ee('Permission')->can('edit_members') || $log->member_id == ee()->session->userdata('member_id')) {
            $editLink = ee('CP/URL')->make('members/profile/', array('id' => $log->member_id));
            $username_display = "<a href=\"" . $editLink . "\">" . $log->Member->screen_name . "</a>";
        } else {
            $username_display = $log->Member->screen_name;
        }
        return $username_display;
    }
}
