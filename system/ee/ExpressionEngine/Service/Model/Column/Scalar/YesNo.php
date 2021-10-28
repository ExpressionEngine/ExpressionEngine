<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Column\Scalar;

use ExpressionEngine\Service\Model\Column\StaticType;

/**
 * Model Service Y/N Typed Column
 */
class YesNo extends StaticType
{
    /**
     * Called when the user gets the column
     */
    public static function get($data)
    {
        return static::isTruthy($data) ? true : false;
    }

    /**
     * Called when the user sets the column
     */
    public static function set($data)
    {
        return $data;
    }

    /**
     * Called when the data is fetched from the db
     */
    public static function load($db_data)
    {
        return $db_data;
    }

    /**
     * Called when the data is stored in the db
     */
    public static function store($data)
    {
        return static::isTruthy($data) ? 'y' : 'n';
    }

    /**
     * Our ee-aware truthyness check
     */
    protected static function isTruthy($data)
    {
        return ($data === true || $data === 'y' || $data === 1);
    }
}

// EOF
