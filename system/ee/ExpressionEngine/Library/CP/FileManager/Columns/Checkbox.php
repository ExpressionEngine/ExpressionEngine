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
 * Checkbox Column
 */
class Checkbox extends EntryManager\Columns\Checkbox
{
    public function renderTableCell($data, $field_id, $file)
    {
        $title = ee('Format')->make('Text', $file->title)->attributeSafe();

        $data = [
            'name' => 'selection[]',
            'value' => $file->getId(),
            //'disabled' => ! $this->canEdit($entry) && ! $this->canDelete($entry),
            'disabled' => $file->isDirectory(),
            'hidden' => $file->isDirectory(),
            'data' => [
                'title' => $title,
                'link' => $file->getAbsoluteURL(),
                'confirm' => lang(strtolower($file->model_type)) . ': <b>' . htmlentities((string) $file->title, ENT_QUOTES, 'UTF-8') . '</b>'
            ]
        ];
        if (ee('Permission')->can('edit_files')) {
            $data['data']['redirect-url'] = ee('CP/URL')->make('files/file/view/' . $file->file_id)->compile();
        }
        return $data;
    }

    public function getEntryManagerColumnSortField()
    {
        return 'file_id';
    }
}
