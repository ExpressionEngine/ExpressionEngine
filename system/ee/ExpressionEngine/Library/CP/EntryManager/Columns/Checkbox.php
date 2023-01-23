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
 * Checkbox Column
 */
class Checkbox extends Column
{
    public function getTableColumnLabel()
    {
        return 'checkbox';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_CHECKBOX
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $title = ee('Format')->make('Text', $entry->title)->attributeSafe();

        return [
            'name' => 'selection[]',
            'value' => $entry->getId(),
            'disabled' => ! $this->canEdit($entry) && ! $this->canDelete($entry),
            'data' => [
                'title' => $title,
                'channel-id' => $entry->Channel->getId(),
                'confirm' => lang('entry') . ': <b>' . $title . '</b>'
            ]
        ];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'entry_id';
    }
}
