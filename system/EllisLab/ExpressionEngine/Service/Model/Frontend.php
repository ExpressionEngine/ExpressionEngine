<?php

namespace EllisLab\ExpressionEngine\Service\Model;

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
 * ExpressionEngine Model Frontend
 *
 * This is the only way the datastore should be communicated with. Either via
 * the query builder using get() or by creating new instances via make().
 *
 * Manually working with instances of the datastore is *not* supported.
 * All other public methods on it should be considered internal and
 * subject to change.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Frontend {

	protected $store;

	/**
	 * @param $store EllisLab\ExpressionEngine\Service\Model\DataStore
	 */
	public function __construct(DataStore $store)
	{
		$this->store = $store;
	}

	/**
	 * Run a query
	 *
	 * @param String $name Model to run the query on
	 */
	public function get($name)
	{
		$builder = $this->store->get($name);
		$builder->setFrontend($this);

		return $builder;
	}

	/**
	 * Create a model instance
	 *
	 * @param String $name Model to create
	 * @param Array  $data Initial data
	 */
	public function make($name, array $data = array())
	{
		return $this->store->make($name, $this, $data);
	}
}