<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator;

class Input {

    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->items[$key] : $default;
    }

    public function all()
    {
        return $this->items;
    }


}