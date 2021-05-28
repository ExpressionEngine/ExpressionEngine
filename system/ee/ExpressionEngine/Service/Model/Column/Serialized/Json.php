<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Column\Serialized;

use ExpressionEngine\Service\Model\Column\SerializedType;

/**
 * Model Service Json Encoded Typed Column
 */
class Json extends SerializedType
{
    protected $data = array();

    /**
     * Called when the column is fetched from db
     */
    public static function unserialize($db_data)
    {
        return strlen($db_data) ? json_decode($db_data, true) : array();
    }

    /**
     * Called before the column is written to the db
     */
    public static function serialize($data)
    {
        return json_encode($data);
    }
}

// EOF
