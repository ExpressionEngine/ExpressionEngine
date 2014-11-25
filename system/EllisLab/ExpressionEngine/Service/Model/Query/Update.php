<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

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

		$object->validate();

		// todo this is yucky
		$gateways = $this->store->getMetaDataReader($object->getName())->getGateways();

		$dirty = $object->getDirty();

		foreach ($gateways as $class)
		{
			$gateway = new $class;
			$gateway->fill($dirty);

			$this->actOnGateway($gateway);
		}
	}

	protected function actOnGateway($gateway)
	{
		$query = $this->store
			->rawQuery()
			->set($gateway->getValues())
			->where($gateway->getPrimaryKey(), $gateway->getId())
			->update($gateway->getTable());
	}
}