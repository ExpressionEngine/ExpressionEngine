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
 * Last Visit Column
 */
class LastVisit extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'last_visit';
    }

    public function renderTableCell($data, $field_id, $member)
    {
        return (!empty($member->last_visit)) ? ee()->localize->human_time($member->last_visit) : '--';
    }
}
