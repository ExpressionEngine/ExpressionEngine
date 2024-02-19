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
 * Model Service Meta Data Reader
 */
class MetaDataReader
{
    protected $name;
    protected $class;
    protected $tables;

    public function __construct($name, $class)
    {
        $this->name = $name;
        $this->class = trim($class, '\\');
    }

    /**
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     *
     */
    public function getPrimaryKey()
    {
        $class = $this->class;

        return $class::getMetaData('primary_key');
    }

    /**
     *
     */
    public function getValidationRules()
    {
        $class = $this->class;
        $validation = $class::getMetaData('validation_rules');

        return $validation ?: array();
    }

    /**
     *
     */
    public function getRelationships()
    {
        $class = $this->class;
        $relationships = $class::getMetaData('relationships');

        return $relationships ?: array();
    }

    /**
     *
     */
    public function getEvents()
    {
        $class = $this->class;
        $relationships = $class::getMetaData('events');

        return $relationships ?: array();
    }

    /**
     * Get binary_comparisons array
     */
    public function getBinaryComparisons()
    {
        $class = $this->class;
        $binary_comparisons = $class::getMetaData('binary_comparisons');

        return $binary_comparisons ?: array();
    }

    /**
     * @return bool
     */
    public function publishesHooks()
    {
        $class = $this->class;
        $name = $class::getMetaData('hook_id');

        return ($name != '');
    }

    /**
     *
     */
    public function getGateways()
    {
        $class = $this->class;
        $gateway_names = $class::getMetaData('gateway_names');

        if (! isset($gateway_names)) {
            $table_name = $class::getMetaData('table_name');

            if (! $table_name) {
                throw new \Exception("Model '{$class}' did not declare a table.");
            }

            return array($table_name => $this->synthesize($class, $table_name));
        }

        $gateways = array();

        $prefix = $this->getNamespacePrefix();

        foreach ($gateway_names as $i => $name) {
            $gateway_class = $prefix . '\\Gateway\\' . $name;
            $gateway = new $gateway_class();
            // the below is causing fields record to be not created, which makes problems for other queries
            // have to skip until we fgure out how to this for Select queries only
            /*$gateway_model = $gateway->getGatewayModel();
            if ($i > 0 && ! is_null($gateway_model) && $gateway_model != 'ChannelField') {
                // if the gateway model is defined, check if it has any objects (e.g. custom fields)
                // if none defined, no reason to query on this gateway
                $fieldsExist = ee('Model')->get($gateway_model)->count(true);
                if ($fieldsExist == 0) {
                    continue;
                }
            }*/
            $gateways[$name] = $gateway;
        }

        return $gateways;
    }

    /**
     *
     */
    protected function synthesize($model, $table_name)
    {
        return new SyntheticGateway($table_name, $model);
    }

    /**
     * @return array [TableName => [columns]]
     */
    public function getTables($cached = true)
    {
        if (isset($this->tables) && $cached) {
            return $this->tables;
        }

        $tables = array();

        $gateways = $this->getGateways();

        foreach ($gateways as $name => $object) {
            $table = $object->getTableName();
            $fields = $object->getFieldList($cached);

            $tables[$table] = $fields;
        }

        $this->tables = $tables;

        return $tables;
    }

    /**
     * Get a table for a given column
     */
    public function getTableForField($field)
    {
        $class = $this->class;
        $table = $class::getMetaData('table_name');

        if ($table) {
            return $table;
        }

        foreach ($this->getTables() as $table => $fields) {
            if (in_array($field, $fields)) {
                return $table;
            }
        }
    }

    /**
     *
     */
    protected function getNamespacePrefix()
    {
        return substr($this->class, 0, strrpos($this->class, '\\'));
    }
}

// EOF
