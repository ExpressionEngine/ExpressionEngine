<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

/**
 * Insert is really just an update without the where.
 */
class Insert extends Update {

	protected function actOnGateway($gateway)
	{
		$query = $this->store
			->rawQuery()
			->set($gateway->getValues())
			->insert($gateway->getTable());
	}
}