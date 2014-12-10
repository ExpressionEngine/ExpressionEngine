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
		$object = $object ?: $this->datastore->make($builder->getFrom());

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
		// todo this is yucky
		$gateways = $this->store->getMetaDataReader($object->getName())->getGateways();

		$dirty = $object->getDirty();

		$results[] = array();

		foreach ($gateways as $gateway)
		{
			$gateway->fill($dirty);
			$results[] = $this->actOnGateway($gateway);
		}
	}

	protected function actOnGateway($gateway)
	{
		$query = $this->store
			->rawQuery()
			->set($gateway->getValues())
			->where($gateway->getPrimaryKey(), $gateway->getId())
			->update($gateway->getTableName());
	}
}