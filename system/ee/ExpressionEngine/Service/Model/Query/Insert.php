<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Query;

/**
 * Insert Query
 */
class Insert extends Update
{
    protected $insert_id;

    public function run()
    {
        $object = $this->builder->getExisting();

        $object->emit('beforeSave');
        $object->emit('beforeInsert');

        $insert_id = $this->doWork($object);
        $object->markAsClean();

        $object->emit('afterInsert');
        $object->emit('afterSave');
    }

    public function doWork($object)
    {
        $this->insert_id = null;

        parent::doWork($object);

        $object->setId($this->insert_id);

        return $this->insert_id;
    }

    /**
     * Set insert id to the first one we get
     */
    protected function setInsertId($id)
    {
        if (! isset($this->insert_id)) {
            $this->insert_id = $id;
        }
    }

    protected function actOnGateway($gateway, $object)
    {
        $values = $gateway->getValues();
        $primary_key = $gateway->getPrimaryKey();

        if (isset($this->insert_id)) {
            $values[$primary_key] = $this->insert_id;
        } elseif ($object->getName() != 'ee:Member' &&
            $object->getName() != 'ee:Role') {
            unset($values[$primary_key]);
        }

        $query = $this->store
            ->rawQuery()
            ->set($values)
            ->insert($gateway->getTableName());

        $this->setInsertId(
            $this->store->rawQuery()->insert_id()
        );
    }
}

// EOF
