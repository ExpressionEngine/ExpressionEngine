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
use ExpressionEngine\Library\CP\Table;

/**
 * Dimensions Column
 */
class Dimensions extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'dimensions';
    }

    public function renderTableCell($data, $field_id, $file)
    {
        $dimensions = explode(" ", $file->file_hw_original);
        if (count($dimensions) > 1) {
            return $dimensions[0] . 'x' . $dimensions[1];
        }
        return '';
    }

    public function getEntryManagerColumnFields()
    {
        return ['file_hw_original'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'file_hw_original';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_INFO
        ];
    }
}
