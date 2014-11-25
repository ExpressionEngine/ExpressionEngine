<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

class Count extends Select {

	/**
	 *
	 */
	public function run()
	{
		$query = $this->buildQuery();
		return $query->count_all_results();
	}
}