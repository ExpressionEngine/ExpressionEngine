<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Monolog\Processor;

use ExpressionEngine\Dependency\Monolog\Processor\ProcessorInterface;

/**
 * Add information about the currently logged in member to the log record
 */
class CurrentMemberProcessor implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record): array
    {
        $record['extra']['current_username'] = ee()->session->userdata('username');
        $record['extra']['current_member_id'] = ee()->session->userdata('member_id');
        return $record;
    }
}