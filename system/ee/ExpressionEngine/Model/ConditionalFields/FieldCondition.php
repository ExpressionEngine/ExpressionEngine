<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\ConditionalFields;

use ExpressionEngine\Service\Model\Model;

/**
 * Condition model
 */
class FieldCondition extends Model
{
    protected static $_primary_key = 'condition_id';
    protected static $_table_name = 'field_conditions';

    protected static $_validation_rules = array(
        'condition_field_id' => 'integer|required',
        'evaluation_rule' => 'required',
        'order' => 'integer'
    );

    protected $condition_id;
    protected $condition_set_id;
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
            'model' => 'ee:ChannelField',
            'from_key' => 'condition_field_id',
            'to_key' => 'field_id',
        )
    );
}
