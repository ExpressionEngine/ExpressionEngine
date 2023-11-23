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

/**
 * Email Column
 */
class Email extends EntryManager\Columns\Column
{
    public function getTableColumnConfig()
    {
        return [
            'encode' => false,
        ];
    }

    public function getTableColumnLabel()
    {
        return 'email';
    }

    public function renderTableCell($data, $field_id, $member)
    {
        if (ee('Permission')->has('can_access_comm')) {
            return "<a class=\"text-muted\" href='" . ee('CP/URL')->make('utilities/communicate/member/' . $member->member_id) . "'>" . $member->email . "</a>";
        }
        return $data;
    }
}
