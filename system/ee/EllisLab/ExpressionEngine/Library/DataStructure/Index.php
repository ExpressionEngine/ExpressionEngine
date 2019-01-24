<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * DataStructure Index
 */
class Index {

	protected $index;
	protected $predicate;

	public function __construct(Closure $predicate)
	{
		$this->index = array();
		$this->predicate = $predicate;
	}

	public function fill($items)
	{
		foreach ($items as $item)
		{
			$this->add($item);
		}
	}

	public function add($item)
	{
		$predicate = $this->predicate;

		$this->index[$predicate($item)] = $item;
	}

	public function get($key)
	{
		return $this->has($key) ? $this->index[$key] : NULL;
	}

	public function has($key)
	{
		return isset($this->index[$key]);
	}
}

// EOF
