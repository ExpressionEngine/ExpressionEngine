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
        // eager loading breaks the pagination
        // so we'll pre-fetch all categories and cache them
        // and then use direct query instead of getting relationships
        // because that would include things like groups and fields that we don't need here
        $allCategories = ee('Model')->get('Category')->fields('cat_id', 'cat_name')->all(true)->getDictionary('cat_id', 'cat_name');

        $related = ee('db')->select('cat_id')
            ->where('entry_id', $entry->entry_id)
            ->get('category_posts')
            ->result_array();
        $categories = array_filter($allCategories, function ($cat_id) use ($related) {
            return in_array($cat_id, array_column($related, 'cat_id'));
        }, ARRAY_FILTER_USE_KEY);

        return implode(", ", $categories);
    }
}
