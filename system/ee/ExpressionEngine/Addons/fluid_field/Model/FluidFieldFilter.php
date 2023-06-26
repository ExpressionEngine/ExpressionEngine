<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FluidField\Model;

/**
 * ExpressionEngine Fluid Field Filter Model
 */
class FluidFieldFilter
{
    protected $attributes = [];

    protected $fields = [
        'name',
        'label',
        'icon'
    ];

    public function __construct($attributes = [])
    {
        $this->setAttributes($attributes);
    }

    public static function make($attributes)
    {
        $instance = new static($attributes);
        return $instance;
    }

    public function setAttributes($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (in_array($attribute, $this->fields)) {
                $this->attributes[$attribute] = $value;
            }
        }
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        return (isset($this->attributes[$key])) ? $this->attributes[$key] : null;
    }
}
