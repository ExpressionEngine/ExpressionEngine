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
use ExpressionEngine\Library\CP\Table;

/**
 * Thumbnail Column
 */
class Thumbnail extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return '';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_THUMB,
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $file, $viewtype = 'list')
    {
        $file_thumbnail = ee('Thumbnail')->get($file)->tag;

        if ($viewtype == 'list') {
            if ($file->isDirectory()) {
                $file_thumbnail = '<a href="' . ee('CP/URL')->make('files/directory/' . $file->upload_location_id, ['directory_id' => $file->file_id]) . '">' . $file_thumbnail . '</a>';
            } elseif (ee('Permission')->can('edit_files')) {
                $file_thumbnail = '<a href="' . ee('CP/URL')->make('files/file/view/' . $file->file_id) . '">' . $file_thumbnail . '</a>';
            }
        }

        return $file_thumbnail;
    }

    public function getEntryManagerColumnSortField()
    {
        return 'file_name';
    }
}
