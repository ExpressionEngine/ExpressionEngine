<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Log;

/**
 * CP Log Model
 */
class CpLog extends Log
{
    public function onBeforeInsert()
    {
        parent::onBeforeInsert();
        $this->setProperty('channel', 'cp');
    }

    public function get__id()
    {
        return $this->getProperty('log_id');
    }

    public function get__act_date()
    {
        return $this->getProperty('log_date');
    }

    public function get__username()
    {
        return $this->getProperty('member_id');
    }

    public function get__action()
    {
        return $this->getProperty('message');
    }
}

// EOF
