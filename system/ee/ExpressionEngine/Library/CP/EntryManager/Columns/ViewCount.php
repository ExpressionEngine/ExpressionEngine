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
 * View Count Columns
 */
class ViewCount extends Column
{
    public function getTableColumnLabel()
    {
        return 'column_' . $this->identifier;
    }

    public function renderTableCell($data, $field_id, $entry, $viewtype = 'list', $pickerMode = false, $addQueryString = [])
    {
        return (int) $entry->{$this->identifier};
    }
}
