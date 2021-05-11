<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Column\ColumnObject;

use DateTime;
use ExpressionEngine\Service\Model\Column\SerializedType;

/**
 * Model Service Timestamp Typed Column
 */
class Timestamp extends SerializedType
{
    /**
     * Called when the column is fetched from db
     */
    public static function unserialize($db_data)
    {
        if ($db_data !== null) {
            return new DateTime("@{$db_data}");
        }
    }

    /**
     * Called before the column is written to the db
     */
    public static function serialize($data)
    {
        return is_object($data) ? $data->getTimestamp() : intval($data);
    }
}

// EOF
