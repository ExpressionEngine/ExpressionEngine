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
        // if file_type is empty, we have likely just ran the migration
        // try to set it based on mime
        if ($file->file_type === null) {
            $file->setProperty('file_type', 'other'); // default
            $mimes = ee()->config->loadFile('mimes');
            $fileTypes = array_filter(array_keys($mimes), 'is_string');
            foreach ($fileTypes as $fileType) {
                if (in_array($file->getProperty('mime_type'), $mimes[$fileType])) {
                    $file->setProperty('file_type', $fileType);
                    break;
                }
            }
            ee('db')->where('file_id', $file->file_id)->update('files', ['file_type' => $file->file_type]);
        }

        return lang('type_' . $file->file_type);
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
