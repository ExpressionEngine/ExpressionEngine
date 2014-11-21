<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\DataStore;

abstract class Query {

	protected $db = NULL;
	protected $builder = NULL;

	public function __construct(DataStore $store, Builder $builder)
	{
		$this->store = $store;
		$this->builder = $builder;
	}

	abstract public function run();
}