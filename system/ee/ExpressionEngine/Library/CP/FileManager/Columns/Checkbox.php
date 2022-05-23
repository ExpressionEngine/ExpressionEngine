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
 * Checkbox Column
 */
class Checkbox extends EntryManager\Columns\Checkbox
{
    public function renderTableCell($data, $field_id, $file)
    {
        $title = ee('Format')->make('Text', $file->title)->attributeSafe();

        return [
            'name' => 'selection[]',
            'value' => $file->getId(),
            //'disabled' => ! $this->canEdit($entry) && ! $this->canDelete($entry),
            'disabled' => $file->isDirectory(),
            'hidden' => $file->isDirectory(),
            'data' => [
                'title' => $title,
                //'channel-id' => $entry->Channel->getId(),
                'confirm' => lang(strtolower($file->model_type)) . ': <b>' . htmlentities($file->title, ENT_QUOTES, 'UTF-8') . '</b>'
            ]
        ];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'file_id';
    }
}
