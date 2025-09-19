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
 * IpAddress Column
 */
class IpAddress extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return $this->identifier;
    }

    public function renderTableCell($data, $field_id, $log)
    {
        return empty($log->ip_address) ? '' : $log->ip_address;
    }
}
