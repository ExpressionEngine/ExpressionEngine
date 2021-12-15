<?php

namespace ExpressionEngine\Service\ConditionalFields\Models;

use ExpressionEngine\Service\Model\Model;

use ExpressionEngine\Field;

class Condition extends Model
{

    protected static $_primary_key = 'id';
    protected static $_table_name = 'field_conditions';

    protected $id;
    protected $condition_set_id;
    protected $field_id;
    protected $operator;
    protected $value;
    protected $order;

    protected static $_relationships = array(
        'ConditionSet' => array(
            'model' => 'ConditionSet',
            'type' => 'BelongsTo'
        )
    );
}
