<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Monolog\Handler;

/**
 * Monolog Handler to write into EE database
 */
class DatabaseHandler extends AbstractEEProcessingHandler
{
    protected function write(array $record): void
    {
        $data = array(
            'site_id' => ee()->config->item('site_id'),
            'channel' => $record['channel'],
            'level' => $record['level'],
            'message' => $record['message'],
            'log_date' => ee()->localize->now
        );
        if (isset($record['context'])) {
            $data['context'] = json_encode($record['context']);
        }
        if (isset($record['extra'])) {
            $data['extra'] = json_encode($record['extra']);
        }
        ee()->db->insert('logs', $data);
    }
}