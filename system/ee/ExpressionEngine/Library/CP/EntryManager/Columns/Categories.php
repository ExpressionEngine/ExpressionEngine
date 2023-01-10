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
    public function getEntryManagerColumnModels()
    {
        return ['Categories'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['Categories.cat_name'];
    }

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
        $categories = $entry->Categories->getDictionary('cat_id', 'cat_name');

        return implode(", ", $categories);
    }
}
