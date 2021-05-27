<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Entry Date Column
 */
class EntryDate extends Column
{
    public function getTableColumnLabel()
    {
        return 'column_entry_date';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return ee()->localize->human_time($entry->entry_date);
    }
}
