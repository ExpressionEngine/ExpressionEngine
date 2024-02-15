<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\LogManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;

/**
 * Checkbox Column
 */
class Checkbox extends EntryManager\Columns\Checkbox
{
    public function renderTableCell($data, $field_id, $log)
    {
        $data = [
            'name' => 'selection[]',
            'value' => $log->getId(),
            'disabled' => ! ee('Permission')->has('can_access_logs'),
            'data' => [
                'confirm' => '<b>' . lang('log_message') . ': </b>' . strip_tags($log->message)
            ]
        ];

        return $data;
    }

    public function getEntryManagerColumnSortField()
    {
        return 'log_id';
    }
}
