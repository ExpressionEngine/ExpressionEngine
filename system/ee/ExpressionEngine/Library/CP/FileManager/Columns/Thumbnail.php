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
            'type' => Table::COL_INFO,
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $file)
    {
        $file_thumbnail = '<img src="' . $file->getThumbnailUrl() . '" style="max-width: 150px; max-height: 100px;" class="thumbnail_img"><span class="tooltip-img" style="background-image:url('.$file->getAbsoluteURL().')"></span>';

        if (ee('Permission')->can('edit_files')) {
            $file_thumbnail = '<a href="' . ee('CP/URL')->make('files/file/view/' . $file->file_id) . '" class=""><img src="'.$file->getThumbnailUrl().'" style="max-width: 150px; max-height: 100px;" class="thumbnail_img"><span class="tooltip-img" style="background-image:url('.$file->getAbsoluteURL().')"></span></a>';
        }

        return $file_thumbnail;
    }
}
