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
 * Comment Count Column
 */
class Comments extends Column
{
    public function getTableColumnLabel()
    {
        return 'comments';
    }

    public function getEntryManagerColumnSortField()
    {
        return 'comment_total';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        if ($entry->comment_total > 0 && ee('Permission')->can('moderate_comments')) {
            return [
                'encode' => false,
                'content' => '(<a href="' . ee('CP/URL')->make('publish/comments/entry/' . $entry->entry_id) . '">' . $entry->comment_total . '</a>)'
            ];
        }

        return '(' . (int) $entry->comment_total . ')';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }
}
