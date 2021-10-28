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
 * Model Service Integer Typed Column
 */
class Integer extends StaticType
{
    /**
     * Called when the user gets the column
     */
    public static function get($data)
    {
        return static::intval($data);
    }

    /**
     * Called when the user sets the column
     */
    public static function set($data)
    {
        return $data;
    }

    /**
     * Called when the column is fetched from db
     */
    public static function load($db_data)
    {
        return static::intval($db_data);
    }

    /**
     * Called before the column is written to the db
     */
    public static function store($data)
    {
        return static::intval($data);
    }

    private static function intval($data)
    {
        return is_scalar($data) ? (int) $data : 0;
    }
}

// EOF
