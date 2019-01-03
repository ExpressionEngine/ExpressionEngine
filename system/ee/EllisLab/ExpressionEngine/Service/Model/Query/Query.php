<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\DataStore;

/**
 * Query
 */
abstract class Query {

	protected $store = NULL;
	protected $builder = NULL;

	public function __construct(DataStore $store, Builder $builder)
	{
		$this->store = $store;
		$this->builder = $builder;
	}

	abstract public function run();
}
