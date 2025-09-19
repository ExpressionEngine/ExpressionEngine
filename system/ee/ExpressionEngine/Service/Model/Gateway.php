<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model;

/**
 * Model Service Gateway
 */
class Gateway
{
    protected static $_field_list_cache;
    protected $_values = array();
    protected static $_gateway_model;

    /**
     *
     */
    public function getTableName()
    {
        return static::$_table_name;
    }

    /**
     *
     */
    public function getFieldList($cached = true)
    {
        if (isset(static::$_field_list_cache[get_class($this)])) {
            return static::$_field_list_cache[get_class($this)];
        }

        $vars = get_object_vars($this);
        $fields = array();

        foreach ($vars as $key => $value) {
            if ($key[0] != '_') {
                $fields[] = $key;
            }
        }

        if (!is_array(static::$_field_list_cache)) {
            static::$_field_list_cache = [];
        }

        return static::$_field_list_cache[get_class($this)] = $fields;
    }

    /**
     *
     */
    public function hasField($name)
    {
        return in_array($name, $this->getFieldList());
    }

    /**
     *
     */
    public function setField($name, $value)
    {
        $this->_values[$name] = $value;
    }

    /**
     *
     */
    public function getPrimaryKey()
    {
        return static::$_primary_key;
    }

    /**
     * model that defines elements fetched by this gateway
     */
    public function getGatewayModel()
    {
        return static::$_gateway_model;
    }

    /**
     *
     */
    public function getId()
    {
        $pk = $this->getPrimaryKey();

        return $this->_values[$pk];
    }

    /**
     *
     */
    public function fill($values)
    {
        foreach ($values as $key => $value) {
            if ($this->hasField($key)) {
                $this->setField($key, $value);
            }
        }
    }

    /**
     *
     */
    public function getValues()
    {
        return $this->_values;
    }
}

// EOF
