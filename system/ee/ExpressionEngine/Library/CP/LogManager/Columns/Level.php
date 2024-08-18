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

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Dependency\Monolog;

/**
 * Level Column
 */
class Level extends Column
{
    public function getTableColumnLabel()
    {
        return 'log_' . $this->identifier;
    }

    public function getTableColumnConfig()
    {
        return [
            'type' => Table::COL_INFO,
            'encode' => false,
        ];
    }

    public function renderTableCell($data, $field_id, $log)
    {
        $level = Monolog\Logger::getLevelName($log->level);
        $color = 'var(--ee-log-level-' . strtolower($level) . ')';
        $vars = [
            'label' => ucfirst($level),
            'class' => 'log_label_' . $level,
            'styles' => [
                'background-color' => 'var(--ee-bg-blank)',
                'border-color' => $color,
                'color' => $color,
            ]
        ];
        return ee('View')->make('_shared/status-tag')->render($vars);
    }
}
