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
