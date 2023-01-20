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
 * Author Column
 */
class Author extends Column
{
    public function getEntryManagerColumnModels()
    {
        return ['Author'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['author_id', 'Author.screen_name', 'Author.username'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'author_id';
    }

    public function getTableColumnLabel()
    {
        return 'author';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return ee('Format')->make('Text', $entry->getAuthorName());
    }
}
