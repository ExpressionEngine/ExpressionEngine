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
 * ExpressionEngine Insert Query
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Insert extends Update {

	protected $insert_ids;

	public function run()
	{
		$object = $this->builder->getExisting();

		$object->emit('beforeSave');
		$object->emit('beforeInsert');

		$insert_id = $this->doWork($object);

		$object->emit('afterInsert');
		$object->emit('afterSave');

	}

	public function doWork($object)
	{
		$this->insert_ids = array();

		parent::doWork($object);

		$insert_id = current($this->insert_ids);

		$object->setId($insert_id);

		return $insert_id;
	}

	protected function actOnGateway($gateway, $object)
	{
		$values = $gateway->getValues();
		unset($values[$gateway->getPrimaryKey()]);

		$query = $this->store
			->rawQuery()
			->set($gateway->getValues())
			->insert($gateway->getTableName());

		$this->insert_ids[] = $this->store->rawQuery()->insert_id();
	}
}