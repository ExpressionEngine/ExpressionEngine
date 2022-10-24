<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
        if (is_object($data)) {
        // is it a datatime object?
            return $data->getTimestamp();
        }
        elseif (is_int($data)) {
        // is it an integer value (i.e. unixtime value)?
            return $data;
        }
        elseif (((string) (int) $data === $data)
        // is it a timestamp as a string (see https://stackoverflow.com/a/2524761/6475781)?
                && ($data <= PHP_INT_MAX)
                && ($data >= ~PHP_INT_MAX)) {
            return intval($data);
        } else {
        // is it a descriptive date string of some kind? 
        // strtotime fails to 'false' so if string does not contain date info this will cause function to fail to false
            return strtotime($data);
        }
    }
}

// EOF
