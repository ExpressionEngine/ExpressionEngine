<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Model\Query;

/**
 * Count Query
 */
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
