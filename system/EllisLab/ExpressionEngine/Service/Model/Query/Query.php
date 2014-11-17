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
 * ExpressionEngine Model Database Query Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Query {

	public function __construct($factory, $graph, $db)
	{
		$this->db = $db;
		$this->graph = $graph;
		$this->factory = $factory;
	}

	/**
	 *
	 */
	public function executeFetch($builder)
	{
		return $this->runStrategy('Fetch', $builder);
	}

	/**
	 *
	 */
	public function executeCount($builder)
	{
		return $this->runStrategy('Count', $builder);
	}

	/**
	 *
	 */
	public function executeDelete($builder)
	{
		return $this->runStrategy('Delete', $builder);
	}

	/**
	 *
	 */
	public function executeUpdate($builder)
	{
		return $this->runStrategy('Update', $builder);
	}

	/**
	 *
	 */
	public function executeCreate($builder)
	{
		return $this->runStrategy('Create', $builder);
	}

	/**
	 *
	 */
	protected function runStrategy($strategy_name, $builder)
	{
		$class = __NAMESPACE__.'\\Strategy\\'.$strategy_name;

		// build the query and run it
		$strategy = new $class($builder, $this->factory, $this->graph);
		$strategy->setDb($this->db);

		return $strategy->run();
	}
}
