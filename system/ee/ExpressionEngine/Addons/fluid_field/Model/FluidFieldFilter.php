<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FluidField\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine Fluid Field Filter Model
 */
class FluidFieldFilter extends Model
{
    protected static $_primary_key = 'filter_id';
    protected static $_table_name = 'fluid_field_filters';

    protected static $_typed_columns = array(
        'filter_id' => 'int',
        'fluid_field_id' => 'int',
        'field_group_id' => 'int',
        'field_id' => 'int',
    );

    /*protected static $_relationships = array(
        'ChannelField' => array(
            'type' => 'belongsTo',
            'model' => 'ee:ChannelField',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidFieldFilter',
                'type' => 'hasMany',
                'weak' => true
            )
        ),
        'ChannelFieldGroup' => array(
            'type' => 'belongsTo',
            'from_key' => 'field_group_id',
            'model' => 'ee:ChannelFieldGroup',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidFieldFilter',
                'type' => 'hasMany',
                'weak' => true
            )
        ),
        'FieldField' => array(
            'type' => 'belongsTo',
            'from_key' => 'fluid_field_id',
            'to_key' => 'field_id',
            'model' => 'ee:ChannelField',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidFieldFilter',
                'type' => 'hasOne',
                'weak' => true
            )
        )
    );*/

    protected static $_events = array(
        'afterDelete'
    );

    protected $filter_id;
    protected $fluid_field_id;
    protected $field_group_id;
    protected $field_id;
    protected $label;
    protected $icon;
    protected $instructions;
    protected $modified_by_member_id;
    protected $modified_date;

    protected $_name;

    public static function make($attributes)
    {
        $instance = new static($attributes);
        foreach ($attributes as $attribute => $value) {
            if (property_exists($instance, $attribute)) {
                $instance->$attribute = $value;
                continue;
            }
            $tempAttribute = '_' . $attribute;
            if (property_exists($instance, $tempAttribute)) {
                $instance->$tempAttribute = $value;
                continue;
            }
        }
        return $instance;
    }

    public function __get($key)
    {
        if ($key == 'name') {
            return $this->_name;
        }
        return (property_exists($this, $key)) ? $this->$key : null;
    }

    public function set__name($name)
    {
        $this->_name = $name;
    }
}
