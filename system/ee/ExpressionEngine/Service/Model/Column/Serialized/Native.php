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
 * Model Service Serialized Typed Column
 */
class Native extends SerializedType
{
    protected $data = array();

    /**
     * Called when the column is fetched from db
     */
    public static function unserialize($db_data)
    {
        return !is_null($db_data) && strlen($db_data) ? unserialize($db_data) : array();
    }

    /**
     * Called before the column is written to the db
     */
    public static function serialize($data)
    {
        return serialize($data);
    }
}

// EOF
