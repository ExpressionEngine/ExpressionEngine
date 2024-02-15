<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\LogManager;
use ExpressionEngine\Library\CP\EntryManager;

/**
 * Member Manager Column Factory
 */
class ColumnFactory extends EntryManager\ColumnFactory
{
    protected static $standard_columns = [
        'site_id' => Columns\SiteId::class,
        'log_date' => Columns\LogDate::class,
        'level' => Columns\Level::class,
        'channel' => Columns\Channel::class,
        'message' => Columns\Message::class,
        'context' => Columns\Context::class,
        'extra' => Columns\Extra::class,
        'ip_address' => Columns\IpAddress::class,
        'checkbox' => Columns\Checkbox::class,
        'preview' => Columns\Preview::class,
    ];

    /**
     * Returns an instance of a column given its identifier. This factory uses
     * the Flyweight pattern to only keep a single instance of a column around
     * as they don't really need to maintain state.
     *
     * @return Column
     */
    public static function getColumn($identifier)
    {
        if (isset(self::$instances[$identifier])) {
            return self::$instances[$identifier];
        }

        if (isset(static::$standard_columns[$identifier])) {
            $class = static::$standard_columns[$identifier];
            self::$instances[$identifier] = new $class($identifier);
        } else {
            return null;
        }

        return self::$instances[$identifier];
    }

    /**
     * Returns Column objects for all custom field columns
     *
     * @return array[Column]
     */
    protected static function getCustomFieldColumns($channel = false)
    {
        return [];
    }

    /**
     * Module tabs not supported
     *
     * @return array
     */
    protected static function getTabColumns()
    {
        return [];
    }
}
