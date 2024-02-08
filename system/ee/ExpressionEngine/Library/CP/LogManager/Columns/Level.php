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
    // the label colors are going from deep green, 
    // which then fades out into light ogange, which then darkens and goes into red
    // the greener the color is - the less is importance, the more red - the more critical
    private static $colors = [
        'DEBUG' => 'var(--ee-success-dark)',
        'INFO' => 'var(--ee-success)',
        'NOTICE' => 'var(--ee-success-light)',
        'WARNING' => 'var(--ee-warning-light)',
        'ERROR' => 'var(--ee-warning)',
        'CRITICAL' => 'var(--ee-warning-dark)',
        'ALERT' => 'var(--ee-error)',
        'EMERGENCY' => 'var(--ee-error-dark)'
    ];

    public function getTableColumnLabel()
    {
        return $this->identifier;
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
        $color = static::$colors[$level];
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
