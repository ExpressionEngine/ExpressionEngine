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
 * Title Column
 */
class Title extends EntryManager\Columns\Title
{
    public function renderTableCell($data, $field_id, $file, $viewtype = 'list', $pickerMode = false, $addQueryString = [])
    {
        $title = $file->title;

        if ($viewtype == 'list') {
            if ($file->isDirectory()) {
                $url = ee('CP/URL')->make('files/directory/' . $file->upload_location_id, array_merge($addQueryString, ['directory_id' => $file->file_id]));
                $title = '<a href="' . $url . '">' . $title . '</a>';
            } elseif (ee('Permission')->can('edit_files')) {
                $title = '<a href="' . ee('CP/URL')->make('files/file/view/' . $file->file_id) . '">' . $title . '</a>';
            }
        }

        if (! $file->exists()) {
            $title .= '<br><em class="faded">' . lang('file_not_found') . '</em>';
        }

        return $title;
    }
}
