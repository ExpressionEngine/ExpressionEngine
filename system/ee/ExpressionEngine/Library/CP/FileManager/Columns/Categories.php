<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;

/**
 * Categories Column
 */
class Categories extends EntryManager\Columns\Categories
{
    public function renderTableCell($data, $field_id, $entry)
    {
        $related = ee('db')->select('cat_id')
            ->where('file_id', $entry->file_id)
            ->get('file_categories')
            ->result_array();

        if (empty($related)) {
            return '';
        }

        $categories = ee()->db
            ->select('cat_name')
            ->from('categories')
            ->where_in('cat_id', array_column($related, 'cat_id'))
            ->get()
            ->result_array();

        return implode(", ", array_column($categories, 'cat_name'));
    }
}
