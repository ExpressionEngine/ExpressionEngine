<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Graph;

use Closure;

abstract class PredicateFilter {

	protected $predicate;

	public function __construct(Closure $predicate)
	{
		$this->predicate = $predicate;
	}

	abstract public function execute($graph);
}