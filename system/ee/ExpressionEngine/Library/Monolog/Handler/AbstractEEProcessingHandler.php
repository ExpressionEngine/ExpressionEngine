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

use ExpressionEngine\Dependency\Monolog\Handler\AbstractProcessingHandler;

/**
 * Abastact for handlers that require EE to be up running
 */
abstract class AbstractEEProcessingHandler extends AbstractProcessingHandler
{
    /**
     * Can we handle it yet?
     *
     * @param array $record
     * @return boolean
     */
    public function isHandling(array $record) : bool
    {
        if (!defined('APP_BOOTED')) {
            return false;
        }
        return parent::isHandling($record);
    }
}