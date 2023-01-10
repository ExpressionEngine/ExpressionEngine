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
 * Sticky Column
 */
class Sticky extends Column
{
    public function getTableColumnLabel()
    {
        return 'sticky_entry';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_ID
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        switch (true) {
            case ($entry->sticky == 1):
            case ($entry->sticky === 'y'):
                $out = lang('yes');

                break;

            default:
                $out = lang('no');

                break;
        }

        return $out;
    }
}
