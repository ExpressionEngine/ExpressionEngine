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
 * Usage Column
 */
class Usage extends EntryManager\Columns\Column
{
    public function getEntryManagerColumnModels()
    {
        return ['FileEntries'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['COUNT(FileEntries.*) AS _usage_count'];
        //need to find a way to store this value in the File model
    }

    public function getEntryManagerColumnSortField()
    {
        return 'modified_by_member_id';
    }

    public function getTableColumnLabel()
    {
        return 'usage';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        return $file->_usage_count;
    }
}
