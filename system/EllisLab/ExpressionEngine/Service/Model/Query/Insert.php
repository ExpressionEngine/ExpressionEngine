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
		$this->insert_ids = array();

		parent::run();

		$object = $this->builder->getExisting();

		return current($this->insert_ids);
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