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

/**
 * Last Editor Column
 */
class LastEditor extends Column
{
    public function getEntryManagerColumnModels()
    {
        return ['LastEditor'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['edit_member_id', 'LastEditor.screen_name', 'LastEditor.username'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'edit_member_id';
    }

    public function getTableColumnLabel()
    {
        return 'last_editor';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return !empty($entry->edit_member_id) ? ee('Format')->make('Text', $entry->LastEditor->getMemberName()) : '';
    }
}
