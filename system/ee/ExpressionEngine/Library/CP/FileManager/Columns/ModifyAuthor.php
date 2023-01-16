<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;

/**
 * Author Column
 */
class ModifyAuthor extends EntryManager\Columns\Column
{
    public function getEntryManagerColumnModels()
    {
        return ['ModifyAuthor'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['modified_by_member_id', 'ModifyAuthor.screen_name', 'ModifyAuthor.username'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'modified_by_member_id';
    }

    public function getTableColumnLabel()
    {
        return 'modified_by';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        $authorName = ($file->modified_by_member_id && $file->ModifyAuthor) ? $file->ModifyAuthor->getMemberName() : '';
        return ee('Format')->make('Text', $authorName);
    }
}
