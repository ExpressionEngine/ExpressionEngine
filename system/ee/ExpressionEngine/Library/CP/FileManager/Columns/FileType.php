<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;

/**
 * FileType Column
 */
class FileType extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'file_type';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        if ($file->model_type == 'Directory') {
            return lang('directory');
        }
        return lang($file->file_type);
    }

    public function getEntryManagerColumnFields()
    {
        return ['file_type'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'file_type';
    }
}
