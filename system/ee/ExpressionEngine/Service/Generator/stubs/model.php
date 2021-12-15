<?php

namespace {{namespace}}\Model;

use ExpressionEngine\Service\Model\Model;

class {{class}} extends Model
{
    // Documentation: https://docs.expressionengine.com/latest/development/services/model/building-your-own.html
    // You can get this all instances of this model by using:
    // ee('Model')->get('{{addon}}:{{class}}')->all();

    protected static $_primary_key = 'id';
    protected static $_table_name = 'my_awesome_table';
    protected $first_name;
    protected $last_name;

    // Add your properties as protected variables here
    protected $id;

    // Example of a property getter
    protected function get__name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Example of a property setter
    protected function set__name($value)
    {
        list($first, $last) = explode(' ', $value);

        $this->setRawProperty('first_name', $first);
        $this->setRawProperty('last_name', $last);
    }
}
