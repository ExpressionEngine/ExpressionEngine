<?php

namespace {{namespace}}\Models;

use ExpressionEngine\Service\Model\Model;

class {{class}} extends Model
{
    // Documentation: https://docs.expressionengine.com/latest/development/services/model/building-your-own.html
    // You can get this model by using:
    // ee('Model')->get('{{namespace}}:{{class}}');

    protected static $_primary_key = 'id';
    protected static $_table_name = 'my_awesome_table';

    // Add your properties as protected variables here
    protected $id;

    public function __construct()
    {
        // Make magic, my friend
    }
}
