<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Query;

/**
 * Update Query
 */
class Update extends Query {

	public function run()
	{
		$builder = $this->builder;
		$object  = $builder->getExisting();

		if ( ! $object)
		{
			$object = $this->store->make(
				$builder->getFrom(),
				$builder->getFacade()
			);
		}

		$backup = $object->getOriginal();
		$this->prepObject($object);

		// Do not save if the object isn't dirty. We cannot do this in the model
		// because the query builder can accept set() calls. Plus, we always want
		// to cascade to children.
		if ( ! $object->getDirty())
		{
			return;
		}

		$object->emit('beforeUpdate', $backup);
		$object->emit('beforeSave');

		// In case values have changed in above events
		$backup = $object->getOriginal();

		$this->doWork($object);
		$object->markAsClean();

		$object->emit('afterSave');
		$object->emit('afterUpdate', $backup);
	}

	/**
	 * Add any set() calls that were on the query builder to the object.
	 */
	protected function prepObject($object)
	{
		foreach ($this->builder->getSet() as $field => $value)
		{
			$object->$field = $value;
		}
	}

	/**
	 * Distribute the data amongs the gateways and save it
	 */
	protected function doWork($object)
	{
		foreach ($this->builder->getSet() as $field => $value)
		{
			$object->$field = $value;
		}

		/*
		$result = $object->validate();

		if ( ! $result->isValid())
		{
			throw new \Exception('Validation failed');
		}
		*/

		// todo this is yucky
		$gateways = $this->store->getMetaDataReader($object->getName())->getGateways();

		$dirty = $object->getDirty();

		if (empty($dirty))
		{
			return;
		}

		foreach ($gateways as $gateway)
		{
			$gateway->fill($dirty);

			$this->actOnGateway($gateway, $object);
		}
	}

	protected function actOnGateway($gateway, $object)
	{
		$values = array_intersect_key(
			$object->getDirty(),
			array_flip($gateway->getFieldList())
		);

		if (empty($values))
		{
			return;
		}

		$query = $this->store
			->rawQuery()
			->set($values);

		if ( ! $object->isNew())
		{
			$query->where($gateway->getPrimaryKey(), $object->getId());

			if ($object->getName() == 'ee:MemberGroup')
			{
				$query->where('site_id', $object->site_id);
			}
		}

		$query->update($gateway->getTableName());
	}
}

// EOF
