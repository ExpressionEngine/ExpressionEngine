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
use ExpressionEngine\Library\CP\Table;
use Mexitek\PHPColors\Color;

/**
 * Status Column
 */
class Categories extends Column
{
    public function getTableColumnLabel()
    {
        return 'column_categories';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_INFO
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        // Querying categories for each entry is a little more wasteful than eager loading a dictionary of all
        // categories but it is much more performant when the site has many categories.  An even better
        // approach would be to make this column aware of all entries being rendered so that it
        // could eager load a limited dictionary for all of the entries that will be shown.

        $related = ee('db')->select('cat_id')
            ->where('entry_id', $entry->entry_id)
            ->get('category_posts')
            ->result_array();

        if(empty($related)) {
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
