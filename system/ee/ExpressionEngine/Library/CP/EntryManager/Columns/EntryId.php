<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;
use ExpressionEngine\Library\CP\Table;

/**
 * Entry ID Column
 */
class EntryId extends Column
{
    public function getTableColumnLabel()
    {
        return 'column_entry_id';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_ID
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return $entry->getId();
    }
}
