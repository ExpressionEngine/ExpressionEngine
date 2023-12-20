<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\ConditionalFields;

use ExpressionEngine\Service\Model\Model;

/**
 * Condition model
 * 
 * should not be used directly, use FieldCondition instead
 */
class Condition extends Model
{
    protected static $_primary_key = 'condition_id';
    protected static $_table_name = 'field_conditions';

    protected $condition_id;
    protected $condition_set_id;
    protected $model_type;
    protected $condition_category_group_id;
    protected $condition_field_name;
    protected $condition_field_id;
    protected $evaluation_rule;
    protected $value;
    protected $order;

    protected static $_relationships = array(
        'FieldConditionSet' => array(
            'type' => 'belongsTo'
        ),
        'UsesConditionField' => array(
            'type' => 'belongsTo',
            'model' => 'ChannelField',
            'from_key' => 'condition_field_id',
            'to_key' => 'field_id'
        )
    );

    protected static $_events = array(
        'afterDelete',
        'beforeSave'
    );

    public function onAfterDelete()
    {
        //if this is the only condition in set, remove the set
        $check = ee('db')->where('condition_set_id', $this->condition_set_id)->count_all_results('field_conditions');
        if ($check == 0) {
            ee('Model')->get('FieldConditionSet', $this->condition_set_id)->delete();
        }
    }

    public function onBeforeSave()
    {
        // placeholder
    }
}
