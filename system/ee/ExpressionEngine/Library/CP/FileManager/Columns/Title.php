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
 * Title Column
 */
class Title extends EntryManager\Columns\Title
{
    public function renderTableCell($data, $field_id, $file)
    {
        $title = $file->title;
        $file_thumbnail = '<img src="' . $file->getAbsoluteURL() . '" style="max-width: 150px; max-height: 100px;" class="thumbnail_img"><span class="tooltip-img" style="background-image:url('.$file->getAbsoluteURL().')"></span>';

        if (ee('Permission')->can('edit_files')) {
            $title = '<a href="' . ee('CP/URL')->make('files/file/view/' . $file->file_id) . '" data-file-id="' . $file->file_id . '" class="m-link">' . $file->title . '</a>';
            $file_thumbnail = '<a href="' . ee('CP/URL')->make('files/file/view/' . $file->file_id) . '" class=""><img src="'.$file->getAbsoluteURL().'" style="max-width: 150px; max-height: 100px;" class="thumbnail_img"><span class="tooltip-img" style="background-image:url('.$file->getAbsoluteURL().')"></span></a>';
        }

        $attrs = array();

        if (!$file->exists()) {
            $attrs['class'] = 'missing';
            $missing_files = true;
            $title .= '<br><em class="faded">' . lang('file_not_found') . '</em>';
        } else {
            // $file_description .= '<br><em class="faded">' . $file->file_name . '</em>';
        }

        return $title;
    }
}
