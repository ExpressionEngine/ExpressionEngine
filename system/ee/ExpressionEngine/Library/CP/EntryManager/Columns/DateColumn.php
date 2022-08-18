<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Entry Date Column
 */
abstract class DateColumn extends Column
{
    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $data = $entry->{$this->identifier};
        return $data ? ee()->localize->human_time(is_int($data) ? $data : $data->format('U')) : '';
    }
}
