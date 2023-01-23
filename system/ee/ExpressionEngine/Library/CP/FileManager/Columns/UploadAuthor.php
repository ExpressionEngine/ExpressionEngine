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
class UploadAuthor extends EntryManager\Columns\Column
{
    public function getEntryManagerColumnModels()
    {
        return ['UploadAuthor'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['uploaded_by_member_id', 'UploadAuthor.screen_name', 'UploadAuthor.username'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'uploaded_by_member_id';
    }

    public function getTableColumnLabel()
    {
        return 'uploaded_by';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        $authorName = ($file->uploaded_by_member_id && $file->UploadAuthor) ? $file->UploadAuthor->getMemberName() : '';
        return ee('Format')->make('Text', $authorName);
    }
}
