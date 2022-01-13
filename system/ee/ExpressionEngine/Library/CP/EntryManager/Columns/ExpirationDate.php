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

use ExpressionEngine\Library\CP\EntryManager\ColumnIconDate;

/**
 * Expiration Date Column
 */
class ExpirationDate extends ColumnIconDate
{
    public function getTableColumnLabel()
    {
        return 'expiration_date';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        if ($this->shouldDisplayIcon()) {
            return $entry->expiration_date ? $this->getIcon($entry, 'expiration_date').ee()->localize->human_time($entry->expiration_date) : '';
        }

        return $entry->expiration_date ? ee()->localize->human_time($entry->expiration_date) : '';
    }
}
