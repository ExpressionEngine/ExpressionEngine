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
 * Developer Log Model
 */
class DeveloperLog extends Log
{
    public function onBeforeInsert()
    {
        parent::onBeforeInsert();
        $this->setProperty('channel', 'developer');
    }

    public function get__timestamp()
    {
        return $this->getProperty('log_date');
    }

    public function get__function()
    {
        return null;
    }

    public function get__description()
    {
        return $this->getProperty('message');
    }
}

// EOF
