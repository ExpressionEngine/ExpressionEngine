<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query\Strategy;

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
 * ExpressionEngine Model Query Update Strategy Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Update extends Strategy {

	const UPDATE_BATCH_SIZE = 100;

	/**
	 *
	 */
	public function run()
	{
		$data = $this->compileSet();
		$filters = $this->builder->getFilters();

		foreach ($data as $table => $data)
		{
			if (empty($data))
			{
				continue;
			}

			$this->applyFilters($filters);
			$this->db->set($data)->update($table);
		}
	}

	/**
	 *
	 */
	protected function compileSet()
	{
		$object = $this->builder->getModelObject();
		$root_model = $this->builder->getRootModel();

		$set_data = $this->builder->getSet();

		if ( ! isset($object))
		{
			$object = $this->getErsatzObject($this->builder->getSet());
		}

		// todo check result
		$object->validate();

		$data = array();
		$gateways = $object->getGateways();

		foreach ($gateways as $gateway)
		{
			$table = $gateway->getMetaData('table_name');
			$data[$table] = $gateway->getDirtyData();
		}

		return $data;
	}

	/**
	 *
	 */
	protected function getErsatzObject($set)
	{
		$object = $this->factory->make($this->builder->getRootModel());

		foreach ($set as $key => $value)
		{
			$object->$key = $value;
		}

		return $object;
	}
}