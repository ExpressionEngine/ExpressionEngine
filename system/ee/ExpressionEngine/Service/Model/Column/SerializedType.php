<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Column;

use ExpressionEngine\Library\Data\Entity;

/**
 * Model Service Serialized Typed Column
 */
abstract class SerializedType implements Type
{
    protected $data = '';

    public static function create()
    {
        return new static();
    }

    public function load($db_data)
    {
        $data = $this->unserialize($db_data);
        $this->data = $data;

        return $data;
    }

    public function store($data)
    {
        return $this->serialize($this->data);
    }

    public function set($data)
    {
        return $this->data = $data;
    }

    public function get()
    {
        return $this->data;
    }
}

// EOF
