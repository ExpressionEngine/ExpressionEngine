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
class Status extends Column
{
    public function getTableColumnLabel()
    {
        return 'column_status';
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_STATUS
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $statuses = $this->getStatuses();

        if (isset($statuses[$entry->status])) {
            $status = $statuses[$entry->status];

            return $status->renderTag();
        }

        return (in_array($entry->status, ['open', 'closed']))
                ? lang($entry->status)
                : $entry->status;
    }

    private function getStatuses()
    {
        static $statuses;

        if (! $statuses) {
            $statuses = ee('Model')->get('Status')->all(true)->indexBy('status');
        }

        return $statuses;
    }
}
