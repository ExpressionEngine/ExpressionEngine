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

/**
 * Expiration Date Column
 */
class ExpirationDate extends DateColumn
{
    public function getTableColumnLabel()
    {
        return 'expiration_date';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $cell = parent::renderTableCell($data, $field_id, $entry);
        $data = $entry->{$this->identifier};
        if (!empty($data) && $data <= ee()->localize->now) {
            $cell = '<i class="fal fa-hourglass-end faded" title="' . lang('expiration_date_past') . '"></i> ' . $cell;
        }
        return $cell;
    }
}
