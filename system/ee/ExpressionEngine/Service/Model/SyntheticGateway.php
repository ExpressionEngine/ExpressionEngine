<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model;

/**
 * Model Service Synthetic Gateway
 */
class SyntheticGateway extends Gateway
{
    protected $table_name;
    protected $model;
    protected $fields;

    public function __construct($table_name, $model)
    {
        $this->table_name = $table_name;
        $this->model = $model;
        $this->fields = $model::getClassFields();
    }

    public function getTableName()
    {
        return $this->table_name;
    }

    public function getFieldList($cached = true)
    {
        return $this->fields;
    }

    public function getPrimaryKey()
    {
        $class = $this->model;

        return $class::getMetaData('primary_key');
    }
}

// EOF
