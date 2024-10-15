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

/**
 * Entry Date Column
 */
class EntryDate extends DateColumn
{
    public function getTableColumnLabel()
    {
        return 'column_entry_date';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $cell = parent::renderTableCell($data, $field_id, $entry);
        $data = $entry->{$this->identifier};
        if (!empty($data) && $data > ee()->localize->now) {
            $cell = '<i class="fal fa-hourglass-start faded" title="' . lang('entry_date_future') . '"></i> ' . $cell;
        }
        return $cell;
    }
}
