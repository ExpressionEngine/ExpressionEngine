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
 * Context Column
 */
class Context extends EntryManager\Columns\Column
{
    public function getTableColumnLabel()
    {
        return 'log_' . $this->identifier;
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $log)
    {
        $out = [];
        foreach ($log->context as $name => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $out[] = $name . ': ' . $value;
        }
        if (count($out) > 5) {
            $out = array_slice($out, 0, 5);
            $out[] = '...';
        }
        return implode('<br>', $out);
    }
}
