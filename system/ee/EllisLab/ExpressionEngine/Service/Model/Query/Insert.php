<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Insert extends Update {

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
		$this->insert_id = NULL;

		parent::doWork($object);

		$object->setId($this->insert_id);

		return $this->insert_id;
	}

	/**
	 * Set insert id to the first one we get
	 */
	protected function setInsertId($id)
	{
		if ( ! isset($this->insert_id))
		{
			$this->insert_id = $id;
		}
	}

	protected function actOnGateway($gateway, $object)
	{
		$values = $gateway->getValues();
		$primary_key = $gateway->getPrimaryKey();

		if (isset($this->insert_id))
		{
			$values[$primary_key] = $this->insert_id;
		}
		elseif ($object->getName() != 'ee:MemberGroup') // TODO MSM this needs to change with msm
		{
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
