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
 * Size Column
 */
class FileSize extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'size';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        if ($file->model_type == 'Directory') {
            return '';
        }
        $unit = 'Kb';
        $fileSize = $file->file_size / 1024;
        if ($fileSize >= 1000) {
            $fileSize = $fileSize / 1024;
            $unit = 'Mb';
            if ($fileSize >= 1000) {
                $fileSize = $fileSize / 1024;
                $unit = 'Gb';
            }
        }
        return round($fileSize) .' ' . $unit;
    }
}
