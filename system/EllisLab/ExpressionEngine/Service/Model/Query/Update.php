<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Query
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Update extends Query {

	public function run()
	{
		$builder = $this->builder;

		$object = $builder->getExisting();
		$object = $object ?: $this->store->make($builder->getFrom(), $builder->getFrontend());

		foreach ($builder->getSet() as $field => $value)
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

		$object->emit('beforeSave');

		// todo this is yucky
		$gateways = $this->store->getMetaDataReader($object->getName())->getGateways();

		$dirty = $object->getDirty();

		if (empty($dirty))
		{
			return;
		}

		$results[] = array();

		foreach ($gateways as $gateway)
		{
			$gateway->fill($dirty);

			$results[] = $this->actOnGateway($gateway, $object);
		}

		$object->emit('afterSave');
	}

	protected function actOnGateway($gateway, $object)
	{
		$values = $gateway->getValues();

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
		}

		$query->update($gateway->getTableName());
	}
}