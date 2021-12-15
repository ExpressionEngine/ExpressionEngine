<?php

namespace ExpressionEngine\Service\ConditionalFields\Models;

class ConditionSet {

    protected static $_primary_key = 'id';
    protected static $_table_name = 'field_condition_sets';

    protected $id;
    protected $field_id;
    protected $match;
    protected $order;

    protected static $_relationships = array(
        'Conditions' => array(
            'model' => 'Condition',
            'type' => 'HasMany'
        )
    );
}