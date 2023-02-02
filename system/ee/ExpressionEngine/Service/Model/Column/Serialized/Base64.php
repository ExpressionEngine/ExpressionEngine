<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Column\Serialized;

use ExpressionEngine\Service\Model\Column\SerializedType;

/**
 * Model Service Base64 Encoded Typed Column
 */
class Base64 extends SerializedType
{
    /**
     * Called when the column is fetched from db
     */
    public static function unserialize($db_data)
    {
        return strlen($db_data) ? base64_decode($db_data) : '';
    }

    /**
     * Called before the column is written to the db
     */
    public static function serialize($data)
    {
        return base64_encode($data);
    }
}

// EOF
