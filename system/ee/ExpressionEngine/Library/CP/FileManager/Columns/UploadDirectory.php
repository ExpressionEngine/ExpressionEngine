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
 * Upload Directory Column
 */
class UploadDirectory extends EntryManager\Columns\Column
{
    public function getEntryManagerColumnModels()
    {
        return ['UploadDestination'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['upload_location_id', 'UploadDestination.name'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'upload_location_id';
    }

    public function getTableColumnLabel()
    {
        return 'upload_location';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        return $file->UploadDestination ? $file->UploadDestination->name : '';
    }
}
