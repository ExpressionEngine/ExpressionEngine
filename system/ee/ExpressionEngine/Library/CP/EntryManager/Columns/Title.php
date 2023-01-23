<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Title Column
 */
class Title extends Column
{
    public function getTableColumnLabel()
    {
        return 'column_title';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }

    public function renderTableCell($data, $field_id, $entry, $viewtype = 'list', $pickerMode = false, $addQueryString = [])
    {
        $title = ee('Format')->make('Text', $entry->title)->convertToEntities();

        if ($this->canEdit($entry)) {
            $edit_link = ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id, $addQueryString);
            $title = '<a href="' . $edit_link . '">' . $title . '</a>';
        }

        if ($entry->Autosaves->count()) {
            $title .= ' <span class="auto-save" title="' . lang('auto_saved') . '">&#10033;</span>';
        }

        return $title;
    }
}
